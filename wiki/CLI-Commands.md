# WP CLI Commands

## Rename Taxonomies

`wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --log=rename-taxonomies.log`

## Merge Terms

`wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv --log=merge-taxonomies.log`

## Delete Terms

`wp boomi taxonomies delete --file=/Users/erikmitchell/bc-migration/src/examples/tax-delete.csv --log=delete-taxonomies.log`

## Update Terms (Parent > Child)

`wp boomi taxonomies update_terms --file=/Users/erikmitchell/bc-migration/src/examples/update-terms.csv --log=update-terms.log`

## Term Validation

Not used in this migration.

## Terminus Commands

Note: Files must be uploaded via the WP Admin page.

`terminus remote:wp boomi.tax-updates -- boomi taxonomies update_terms --file=/code/wp-content/uploads/bc-migration/update-resources.csv --log=update-resource-terms.log`

<!-- VERSION-FOOTER -->
<p align="center"><sub><em>Documentation Version: v0.1.0</em></sub></p>
