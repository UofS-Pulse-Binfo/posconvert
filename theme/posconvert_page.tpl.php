<?php
/**
 * @file
 * Master template file of posconvert module.
 *

 * Available variables:
 * - $posconvert_sample_file: Sample file containing text data of LC 08 position shown to user.
 */
?>

<div id="div-page-ctr-panel">
  <div class="messages warning">
    <p>
      <strong>This is a <em>Beta Release</em> and is still undergoing testing.</strong>
      <em style="color: red; font-weight: bold">Important:</em>
      Posconvert is limited to files that are less than <?php print $posconvert_max_file_limit; ?>b or <?php print number_format($posconvert_max_line_limit); ?> lines (not including the header).
      It will take up to an hour to convert <?php print number_format($posconvert_max_line_limit); ?> lines. If you have a larger file, please
      contact us directly for help converting your file.
    </p>

    Double check that you have:
    <ul>
      <li>Chosen the correct columns for the name of the backbone (ie. contig) and position on the genome.</li>
      <li>Ensure that your converted file contains the new marker positions on <em>Lc1.2</em> in the appropriate columns.</li>
    </ul>
    As always, please don’t hesitate to contact us if you run into issues or have additional feedback!
  </div>

  <h2>Convert marker positions from <em>L.culinaris</em> genome version 0.8 to 1.2</h2>
</div>

<div class="div-step-container">
  <span>1.</span>
  <h3>Paste your input text in tab-delimited format into the following box.</h3>
  <p>Your data may contain headers or any lines you want ignored so long as the line(s) begin with 1 or more '#' (as with a VCF file, for example).
  <br />
  <a href="#" id="link-slide">Show sample file</a></p>
  <div id="div-slide-container">
    <textarea id="txt-sample-file-field" rows="15" readonly title="Sample text data"><?php print file_get_contents($posconvert_sample_file); ?></textarea>
  </div>
</div>

<?php
if ($posconvert_has_job) {
?>

  <div class="messages status warning">We have detected that you have an ongoing Position Convert request. Please allow the request to complete before doing another conversion.</div>

<?php
}
else {

  // Render form elements.
  print drupal_render_children($form);

}
?>
