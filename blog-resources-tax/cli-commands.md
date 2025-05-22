# WP CLI Commands

## Rename Taxonomies

`wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --log=rename-taxonomies.log`

## Merge Terms

`wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv --log=merge-taxonomies.log`

## Delete Terms

`wp boomi taxonomies delete --file=/Users/erikmitchell/bc-migration/src/examples/tax-delete.csv --log=delete-taxonomies.log`

## Update Terms (Parent > Child)

`wp boomi taxonomies update_terms --csv=/Users/erikmitchell/bc-migration/src/examples/update-terms.csv --log=update-terms.log`

## Term Validation

Not used in this migration.

## Terminus Commands

Note: Files must be uploaded via the WP Admin page.

terminus remote:wp boomi.taxcli -- boomi taxonomies update_terms content-type --csv=/code/wp-content/uploads/bc-migration/update-resource-types.csv --log=update-resource-types.log
