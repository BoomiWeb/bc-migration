Rename Taxonomies

# dry run

wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --dry-run

# log output
wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --log=rename-terms.log

# rename single
wp boomi taxonomies rename industries "M&A" "Mergers & Acquisitions" --new-slug="mergers-acquisitions" --dry-run --log=rename.log
wp boomi taxonomies rename industries "Mergers. & Acquisitions" "Mergers & Acquisitions"

Merge Taxonomies

# single

wp boomi taxonomies merge products "Foo Boo|Bar Foo" "Foo Bar" --post-type=blog --delete-old --log=merge.log
wp boomi taxonomies merge products "Foo Boo|Bar Foo" "Foo Bar" --post-type=blog

# batch

wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv --log=merge.log
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv --delete-old
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv

# dry run

wp boomi taxonomies merge products "Foo Boo|Bar Foo" "Foo Bar" --post-type=blog --dry-run
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv --log=merge.log --dry-run

Delete Terms

# single

wp boomi taxonomies delete industries "Foo Boo|Bar Foo" --log=delete.log --dry-run [WORKS]
wp boomi taxonomies delete industries "Foo Boo|Bar Foo" --log=delete.log [WORKS]

# batch

wp boomi taxonomies delete --file=/Users/erikmitchell/bc-migration/src/examples/tax-delete.csv --log=delete.log 
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv

# dry run

wp boomi taxonomies delete industries "Foo Boo|Bar Foo" --log=delete.log --dry-run [WORKS]
wp boomi taxonomies delete --file=/Users/erikmitchell/bc-migration/src/examples/tax-delete.csv --log=delete.log --dry-run [WORKS]


# Errors

wp boomi taxonomies merge products "A|B" "C" --post-type=invalid_post_type
wp boomi taxonomies merge invalid-taxonomy "A|B" "C"
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv --post-type=invalid_post_type
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge-bad.csv
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/invalid-file.csv

# Testing
wp boomi taxonomies delete industries "Foo Boo|Bar Foo" --log=delete.log [X]
wp boomi taxonomies delete --file=/Users/erikmitchell/bc-migration/src/examples/tax-delete.csv --log=delete.log  []
wp boomi taxonomies delete industries "Foo Boo|Bar Foo" --log=delete.log --dry-run []

# Term Validation

## Single

## Bulk
wp term-validator category --file=terms.csv --field=slug --delete --dry-run