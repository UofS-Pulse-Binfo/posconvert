<?php

/**
 * A function to convert single base-pair positions from one genome version to another, if
 * provided with the appropiate agp file.
 *
 * ****** Adapted from Connor Burbridge's perl script here: ******
 * https://gist.github.com/cbe453/9a0a336963d343007598efe6f4335303
 *
 * This function will take in any tab delimited file and convert the specified columns,
 * with contigs and position numbers, and convert them from old genome positions to an
 * updated version of the positions.
 *
 *  @param $input_file
 *    A tab-delimited text file that the user wishes to convert the positions of SNPs or markers
 *    to another genome version. Thus, the file must contain a column dedicated to backbone
 *    and another column for position. The remainder of the file will remain intact in the
 *    output.
 *  @param $agp_file
 *    This is the genome lookup file which is often generated after a new genome version is
 *    released. This file must meet the AGP file format specifications found here:
 *    https://www.ncbi.nlm.nih.gov/assembly/agp/AGP_Specification/
 *  @param $backbone
 *    A positive integer denoting which column contains backbone information within the
 *    input file.
 *  @param $position
 *    A positive integer denoting which column contains position information within the
 *    input file.
 *  @param $output_file
 *    An optional name for the file to be written to. If not provided, the default name will
 *    be the input file name with '.posconverted' appended to it.
 *  @param $job_id
 *    An integer containing the Job Id number when job request is created.
 *  @param $total_lines
 *    An integer containing total number of lines present in a file.
 */

function posconvert_convert_SNPs_with_agp($input_file, $agp_file, $backbone, $position, $output_file, $job_id, $total_lines) {
  print 'Input file: '      . $input_file  . "\n";
  print 'Output file: '     . $output_file . "\n";
  print 'AGP file: '        . $agp_file    . "\n";
  print 'Backbone: '        . $backbone    . "\n";
  print 'Position: '        . $position    . "\n";
  print 'Number of Lines: ' . $total_lines . "\n";
  print 'Job ID: '          . $job_id      . "\n";
  print "--------------------------------------------\n";

  // ----- FILE VALIDATION -----
  $input_file = trim($input_file);
  if (!$input_file) { die ("ERROR: Unable to find input file: $input_file!\n"); }
  $agp_file = trim($agp_file);
  if (!$agp_file) { die ("ERROR: Unable to find agp file: $agp_file!\n"); }
  $agp_file_in_gff_format = $agp_file . '.sorted.gff.gz';
  print 'indexed gff format agp file:' . $agp_file_in_gff_format . "\n\n";
  if (!$agp_file_in_gff_format) { die ("ERROR: Unable to find agp file: $agp_file_in_gff_format!\n"); }
  // ----- PARAMETER VALIDATION -----
  // Check that both our backbone and position parameters are positive integers
  if (!$backbone || !ctype_digit($backbone)) { die ("ERROR: Backbone column is not a positive integer ($backbone).\n"); }
  if (!$position || !ctype_digit($position)) { die ("ERROR: Position column is not a positive integer ($position).\n"); }
  // Decrement each parameter thanks to arrays starting at 0!
  $backbone--; $position--;

  // Open our files
  $INFILE = fopen($input_file, 'r') or die ("ERROR: Unable to open $input_file!\n");

  //$AGPFILE = fopen($agp_file, 'r') or die ("ERROR: Unable to open $agp_file!\n");
  if ($output_file === NULL) { $output_file = $input_file . '.posconverted'; }
  $OUTFILE = fopen($output_file, 'w') or die ("ERROR: Unable to create $output_file!\n");

  //$AGP_file = file($agp_file);
  $AGP_file = $agp_file_in_gff_format;

  print "STARTING.\n";
  print "Job Id: " . $job_id . "\n";
  // ----- PROCESS -----
  $i = 0;
  while(!feof($INFILE)) {
    $i++;
    $percent = ($i / $total_lines) * 100;
    if ($percent % 5 == 0) {
      db_query('UPDATE {tripal_jobs} SET progress = :percent WHERE job_id = :id',
        array(':percent' => round($percent), ':id' => $job_id));

      drush_print(round($percent, 0) . '% Complete...');
    }
    $current_line = fgets($INFILE);
    if (empty($current_line)) continue;

    // Check for commented lines. @ASSUMPTION: Comment lines begin with '#'
    if (preg_match('/^#/', $current_line)) {
      # Just print as-is in the output file
      fwrite($OUTFILE, $current_line);
      continue;
    }

    $current_line = rtrim($current_line);

    ///////
    // Array to hold values required.
    $arr_values = array(
      'current_line' => $current_line,
      'agp_file' => $AGP_file,
    );

    $is_valid = posconvert_is_valid($arr_values, $backbone, $position);
    if (isset($is_valid['error'])) {
      // Array to hold information (backbone, position, etc.) relayed to user
      // when error is detected.

      // Recount columns.
      $columns = explode("\t", $current_line);
      // See if function returned a value for this element.
      $backbone_found = isset($is_valid['backbone_found'])
        ? $is_valid['backbone_found'] : 0;

      $arr_info = array(
        'num_cols'       => count($columns),
        // Backbone.
        'backbone'       => $backbone + 1,
        'backbone_val'   => $columns[$backbone],
        'backbone_found' => $backbone_found,
        // Position.
        'position'       => $position + 1,
        'position_val'   => $columns[$position],
      );

      // The result of the validation and a flag to either terminate the script or continue on.
      // Print the error.
      $message = posconvert_error_message($is_valid['error'], $arr_info);
      print $message . "\n";
      // Should the script terminate or not.
      if ($is_valid['end']) {
        // When error, terminate script;
        die();
      }
      else {
        // Next line.
        continue;
      }
    }
    else {
      // No error write this line.
      // And print it to the output file!
      $split_line = $is_valid;
      fputcsv($OUTFILE, $split_line, "\t");
    }
    ////////

  }

  print "FINISHED.\n";

  fclose($INFILE);
  fclose($OUTFILE);
}


/**
 * Function: validate backbone and position.
 *
 * @param $arr_val
 *   An array containing 1. The current line 2. AGP file.
 * @param $backbone
 *   An integer value corresponding to a column # representing the backbone.
 * @param $position
 *   An integer value corresponding to a column # representing the position.
 *
 * @return array
 *   An array containing relevant error message and a value indicating whether to terminate or continue the script.
 *   When current line passed all checks, function will return the converted position to be written to file.
 */
function posconvert_is_valid($arr_val, $backbone, $position) {
  // Current line to check.
  $current_line = $arr_val['current_line'];
  // Break the current line to individual values.
  $split_line = explode("\t", $current_line);
  // Count coulumns available by counting the values.
  $num_columns = count($split_line);

  // Begin check.
  if ($num_columns < 2) {
    // NOT ENOUGH COLUMNS
    return array(
      'error' => 'not_enough_cols',
      'end'   => TRUE,
    );
  }

  if ($backbone > $num_columns) {
    // BACKBONE IS GREATER THAN THE # OF COLUMNS
    return array(
      'error' => 'backbone_greaterthan_cols',
      'end'   => TRUE,
    );
  }

  if ($position > $num_columns) {
    // POSITION IS GREATER THAN THE # OF COLUMNS
    return array(
      'error' => 'position_greaterthan_cols',
      'end'   => TRUE,
    );
  }

  if (!ctype_digit($split_line[$position])) {
    // POSITION NOT A NUMBER.
    return array(
      'error' => 'position_not_number',
      'end'   => TRUE,
    );
  }

  // Check the backbone actually makes sense. If the user supplies the wrong column #,
  // this could lead to spurious hits in the .agp file.
  $valid_backbone_list = array('ontig', 'caffold', 'hr');
  $valid = false;

  foreach ($valid_backbone_list as $item) {
    if (false !== strpos($split_line[$backbone], $item)) {
      $valid = true;
    }
  }

  if ($valid === false) {
    // INVALID TEXT IN BACKBONE
    return array(
      'error' => 'expected_chars',
      'end'   => TRUE,
    );
  }

  // Now perform a simple grep to get the line in the agp file that matches our backbone
  $AGP_file = $arr_val['agp_file'];
  $command = 'tabix ' . $AGP_file . ' ';
  $command .= $split_line[$backbone] . ':' . $split_line[$position] . '-' . $split_line[$position];
  //$matches = preg_grep("/\t$split_line[$backbone]\t/", $AGP_file);
  $matches = shell_exec($command);
  $matches = trim($matches);
  print $matches . "\n";
  $matches_exp = explode("\n", $matches);
  $count_match = count($matches_exp);

  if ($count_match > 1) {
    // UNEXPECTED # OF MATCHES.
    return array(
      'backbone_found' => $count_match,
      'error' => 'more_match',
      'end'   => FALSE,
    );
  }

  if ($count_match < 1) {
    // NO MATCH.
    return array(
      'backbone_found' => 0,
      'error' => 'zero_match',
      'end'   => FALSE,
    );
  }

  // Check Position.
  $agp_line = explode("\t", $matches_exp[0]);
  $agp_line = explode(";", $agp_line[8]);

  // Build the new output line with converted backbone and position
  $new_backbone = $agp_line[0];
  $old_position = $split_line[$position];
  $new_position = (($agp_line[1] + $old_position) - 1);

  if (empty($new_position)) {
    // POSITION IS EMPTY.
    return array(
      'error' => 'position_empty',
      'end'   => FALSE,
    );
  }

  // Convert values.
  $split_line[$backbone] = $new_backbone;
  $split_line[$position] = $new_position;

  // No errors return converted line.
  return $split_line;
}

/**
 * Function: Read agp file into one array with key
 *
 * @param $agp_in_file
 *   agp file, should have checked path exist and readable
 *
 * @return array
 *   An array containing unique key to make connection for convert
 */
function read_agp_file_2_array($agp_lookup_file){




}


/**
 * Function: Generate error message.
 *
 * @param $error_code
 *   A string indicating the type of error.
 * @param $args
 *   An array containing information (backbone, position, etc.) to be part of the error message.
 *
 * @return string
 *   An string containing the relevant error message and information.
 */
function posconvert_error_message($error_code, $args) {
  $message = '';

  switch($error_code) {
    case 'not_enough_cols':
      // Only in DRUSH terminal.
      $message = 'Less than 2 columns detected in the souce file.';
      break;

    case 'backbone_greaterthan_cols':
      // Only in DRUSH terminal.
      $message = 'Column number for backbone cannot exceed number of columns.';
      break;

    case 'position_greaterthan_cols':
      // Only in DRUSH terminal.
      $message = 'Column number for position cannot exceed number of columns.';
      break;

    case 'expected_chars':
      $message = 'Backbone name is expected to contain "contig", "scaffold" or "chr" for backbone.';
      break;

    case 'more_match':
      $message = 'Unexpected number of matches in the AGP file for backbone.';
      break;

    case 'zero_match':
      $message = 'Could not find a match for backbone.';
      break;

    case 'position_empty';
      $message = 'Position returned an empty value.';
      break;

    case 'position_not_number':
      $message = 'Position column selected returned a non-numeric value.';

  }

  if ($args) {
    // For admin.
    // Add # of columns, backbone and position information.
    // Example: 20 Columns Found (BACKBONE: Col #4 [col_value - 34 Found] | POSITION: Col #2 [col_value - 0 Found])
    $message .= ' ' . $args['num_cols'] . 'Columns Found (BACKBONE: Col #' . $args['backbone'] . ' [' . $args['backbone_val'] . ' - ' . $args['backbone_found'] . ' Found]' . '| POSITION: Col #' . $args['position'] . ' [' . $args['position_val'] . '])';
  }

  return $message;
}
