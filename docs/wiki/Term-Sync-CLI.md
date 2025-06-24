# Term Sync CLI

## Usage Examples

### Basic term matching:

```bash
# Match all terms between blog_posts and products
wp taxonomy-sync match --source=blog_posts --target=products --post-type=post

# Match specific term across multiple taxonomies
wp taxonomy-sync match --source=blog_posts --target=products,topics --post-type=custom_post --term="API Management"

# Dry run to see what would happen
wp taxonomy-sync match --source=blog_posts --target=products --post-type=post --dry-run
```

### Bulk CSV processing:

```bash
wp taxonomy-sync bulk --csv-file=sync.csv --post-type=post --dry-run
```

### List terms for reference:

```bash
wp taxonomy-sync list-terms --taxonomy=blog_posts
wp taxonomy-sync list-terms --taxonomy=products --search="API"
```

## CSV File Format

Create a CSV file with these columns:

```csv
post_id,source_taxonomy,target_taxonomy,term_name
123,blog_posts,products,API Management
124,blog_posts,topics,API Management
125,blog_posts,products,Cloud Computing
```