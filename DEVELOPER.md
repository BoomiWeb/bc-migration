Taxonomy Rename
# dry run
wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --dry-run

#log output
wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --log=rename-terms.log

#rename single
wp boomi taxonomies rename industries "M&A" "Mergers & Acquisitions" --new-slug="mergers-acquisitions" --dry-run --log=rename.log
wp boomi taxonomies rename industries "M&A" "Mergers & Acquisitions" --new-slug="mergers-acquisitions" --log=rename.log
