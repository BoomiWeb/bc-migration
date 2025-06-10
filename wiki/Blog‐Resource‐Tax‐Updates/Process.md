# Process

## Upload CSV Files

1. WP Admin > Tools > Taxonomy Migration
2. Upload CSV Files
3. Copy "Upload Date" - this is actually the file path

## Process CSV Files

This is done using the WP CLI command `wp boomi taxonomies * --file=* --log=*`

For Terminus, this is done using the Terminus command `terminus remote:wp boomi.tax-updates -- boomi taxonomies * --file=* --log=*`

### Blogs

#### Blog Categories

We need to merge and migrate blog categories (`blog_posts`) terms first.

###### Merge

`terminus remote:wp boomi.dev -- boomi taxonomies merge --file=/code/wp-content/uploads/bc-migration/blog-cat-merge.csv --log=merge-blog-cats.log --delete-old`

###### Migrate

`terminus remote:wp boomi.dev -- boomi taxonomies migrate --file=/code/wp-content/uploads/bc-migration/blog-cat-migrate.csv  --log=migrate-blog-cats.log --delete`

##### Rename

`terminus remote:wp boomi.dev -- boomi taxonomies rename --file=/code/wp-content/uploads/bc-migration/blog-cat-rename.csv --log=rename-blog-terms.log`

##### Remove Terms

`terminus remote:wp boomi.tax-updates -- boomi taxonomies delete --file=/code/wp-content/uploads/bc-migration/delete.csv --log=delete-blog-terms.log`

### Resources

#### Update Terms (Parent > Child)

`terminus remote:wp boomi.tax-updates -- boomi taxonomies update_terms --file=/code/wp-content/uploads/bc-migration/update-resources.csv --log=update-resource-terms.log`

#### Rename Terms

`terminus remote:wp boomi.tax-updates -- boomi taxonomies rename --file=/code/wp-content/uploads/bc-migration/rename-resources.csv --log=rename-resource-terms.log`

#### Merge Terms

`terminus remote:wp boomi.tax-updates -- boomi taxonomies merge --file=/code/wp-content/uploads/bc-migration/merge-resources.csv --log=merge-resource-terms.log --delete-old`

<!-- VERSION-FOOTER -->
<p align="center"><sub><em>Documentation Version: v0.1.0</em></sub></p>
