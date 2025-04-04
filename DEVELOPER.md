Rename Taxonomies

# dry run

wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --dry-run

# log output
wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --log=rename-terms.log

# rename single
wp boomi taxonomies rename industries "M&A" "Mergers & Acquisitions" --new-slug="mergers-acquisitions" --dry-run --log=rename.log
wp boomi taxonomies rename industries "M&A" "Mergers & Acquisitions" --new-slug="mergers-acquisitions" --log=rename.log

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
