Provides an interface for users to provide a tab-delimited file in any format (e.g. VCF, custom excel export) and have the positions in it converted from one genome version to another. This works by making the user specify which columns contain the backbone and position information and then using an AGP file to replace those columns with the new genome information.

[Screenshot of module interface.](includes/posConvert.screenshot.png)

## Known Limitations
- **Currently specific to *Lens culinaris* v0.8 to v1.2**
- Expects exact matches in the AGP file for the backbone names.
- If a match isn't found in a given line then it is removed from the output. The admin is told on the command line but the user isn't due to a limitation in the Tripal Download API. If the line is within the first 50, the user will be told.
