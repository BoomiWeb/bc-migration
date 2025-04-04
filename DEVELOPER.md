Rename Taxonomies
# dry run
wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --dry-run

#log output
wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --log=rename-terms.log

#rename single
wp boomi taxonomies rename industries "M&A" "Mergers & Acquisitions" --new-slug="mergers-acquisitions" --dry-run --log=rename.log
wp boomi taxonomies rename industries "M&A" "Mergers & Acquisitions" --new-slug="mergers-acquisitions" --log=rename.log

Merge Taxonomies
/*
#csv
taxonomy,from_terms,to_term,post_type
products,"B2B Integration|CRM Integration","Integration",custom_post_type
industries,"Retail|E-Commerce","Retail & E-Commerce",post
*/


# single
wp boomi taxonomies merge products "B2B Integration|CRM Integration" "Integration" --delete-old
wp boomi taxonomies merge products "CRM|ERP" "Integration" --post-type=custom_post_type --dry-run --log=merge.log


# batch
wp boomi taxonomies merge --file=merge-terms.csv --log=merge.log
wp boomi taxonomies merge --file=merge.csv

# dry run
wp boomi taxonomies merge --file=merge-terms.csv --log=merge.log --dry-run
wp boomi taxonomies merge --file=merge.csv --dry-run --log=merge.log
