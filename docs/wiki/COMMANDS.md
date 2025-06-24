# WP CLI Commands

A collection of examples for new `wp boomi taxonomies` CLI commands to help rename, merge, delete, validate, and update taxonomy terms.

---
    
## Rename Taxonomies

Rename a single term or bulk terms via a CSV file.

### Command Options

```
[<taxonomy> <old_term> <new_name>]    Taxonomy, old term, and new name for single term rename.
[--new-slug=<new-slug>]               Optional new slug for single rename.
[--file=<file>]                       Path to CSV file for bulk rename.
[--dry-run]                           Only simulate the changes; no actual updates.
[--log=<logfile>]                     File to write logs to.
```

### CSV Format

```
taxonomy,old_term,new_name
subcats,Fruit,Fruits
subcats,Bacon,Bacon Power
subcats,Monkey Power,Monkey Power Max
subcats,Test,Testing
```

### Examples

```
wp boomi taxonomies rename industries "Mergers. & Acquisitions" "Mergers & Acquisitions"
```

> Rename a single term.

```
wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv
```

> Rename terms from a CSV file.

---

## Merge Taxonomies

Merge terms within a taxonomy

### Command Options

```
[<taxonomy> <from_terms> <to_term>]   Taxonomy, pipe-separated list of old terms, and destination term.
[--file=<file>]                       Path to CSV file for batch merge.
[--delete-old]                        Delete the old terms after merging.
[--dry-run]                           Simulate actions without making changes.
[--log=<logfile>]                     Path to a log file for results.
```

### CSV Format

```
taxonomy,from_terms,to_term,post_type
products,"Das Flow","Integration",blog
industries,"Foo|Bar","Foo Bar",post
invalid,"Foo|Bar","Foo Bar",post
```

### Examples
```
wp boomi taxonomies merge products "Foo Boo|Bar Foo" "Foo Bar"
```

> A single-term merge.

```
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv
```

> Merges terms in bulk from CSV.

---

## Delete Terms

Delete a single term or bulk terms via a CSV file.

### Command Options

```
[<taxonomy> <term>]                  Taxonomy and term to delete.
[--file=<file>]                      Path to CSV file for bulk delete.
[--dry-run]                          Only simulate the changes; no actual updates.
[--log=<logfile>]                    File to write logs to.
```

### CSV Format

```
taxonomy,term
products,"Das Flow"
industries,"Foo Boo"
industries,"Foo Bar"
```

### Examples

```
wp boomi taxonomies delete industries "Foo Boo|Bar Foo"
```

> Delete a single term.

```
wp boomi taxonomies delete --file=/Users/erikmitchell/bc-migration/src/examples/tax-delete.csv
```

> Delete multiple terms from a CSV file.

---

## Term Validation

Validate, sync, and optionally clean up taxonomy terms.

### Command Options

```
<taxonomy>                           The taxonomy to validate and sync.
[--terms=<terms>]                    Comma-separated list of term identifiers.
[--file=<file>]                      Path to a CSV file with one term per line.
[--field=<field>]                    Field type to match terms by. Accepts: name, slug, id. Default is name.
[--delete]                           Delete terms not in the provided list.
[--dry-run]                          Perform a dry run without modifying anything.
[--log=<logfile>]                    Path to a log file for results.
``` 

### CSV Format

```
TODO
```

### Examples

```
wp boomi taxonomies term-validator category --terms="News,Updates"
```

> Validates terms against existing ones in the `category` taxonomy.

```
wp boomi taxonomies term-validator category --file=/Users/erikmitchell/bc-migration/src/examples/categories.csv
```

> Validate terms from a file.

```
wp boomi taxonomies term-validator category --file=/Users/erikmitchell/bc-migration/src/examples/categories.csv --delete
```

> Validation and deletion of invalid terms from CSV.

---

## Term Updater (Parent > Child Terms)

Updates or creates taxonomy terms with parent-child relationships.

### Command Options

```
<taxonomy>                           The taxonomy name (e.g. category, post_tag, content-type).
[<terms>]                            A string defining parent > child relationships.
[--csv=<file>]                       Path to a CSV file defining parent > children.
[--dry-run]                          If set, no changes will be made.
[--log=<logfile>]                    Path to a log file for results.
```

### CSV Format

```
taxonomy,terms
content-type,"News & Updates > Press Release, News"
content-type,"Briefs > Executive Brief, Industry Brief, Product Brief, Service Brief"
industries,"Agriculture > Foo"
```

### Examples

```
wp boomi taxonomies update_terms content-type "News & Updates > Press Release, News"
```

> Moves "Press Release" and "News" under parent "News & Updates".


```
<!-- wp boomi taxonomies update_terms content-type --csv=/Users/erikmitchell/bc-migration/src/examples/update-terms.csv -->
wp boomi taxonomies update_terms --csv=/Users/erikmitchell/bc-migration/src/examples/update-terms.csv
```

> Updates taxonomy hierarchy from a CSV file.

---

## Change Post Type(s) w/ Data Migration

Changes a post from one post type to another and has options to migrate custom taxonomies and post meta.

### Command Options

```
[--from=<post_type>]	The current post type.
[--to=<post_type>]	The new post type to migrate to.
[--post_ids=<ids>]	A comma-separated list of post IDs to migrate.
[--taxonomy=<slug>]	A taxonomy term slug to migrate all matching posts.
[--taxonomy-type=<type>]	The type of taxonomy to migrate.
[--file=<file_path>]	Path to a CSV file with post IDs to migrate.
[--log=<name>]	Name of the log file.
[--copy-tax]	Copy all post taxonomies.
[--tax-map=<file_path>]		Path to a JSON file with custom taxonomy mappings.
[--meta-map=<file_path>]	Path to a JSON file with custom meta mappings.
```

### CSV Format

```
from,to,post_ids
resource-library,events,188931|188930|188916|188906|188904
```

### Examples

```
wp boomi migrate post-type --from=post --to=page --post_ids=177509,177510
```

> Changes post(s) from the post type "post" to "page".

```
wp boomi migrate post-type --from=post --to=page --taxonomy=api
```

> Changes all posts from the post type "post" to "page" with the "api" taxonomy.

```
wp boomi migrate post-type --file=/Users/erikmitchell/bc-migration/examples/post-type.csv
```

> Change posts types via a CSV file.

```
wp boomi migrate post-type --from=post --to=page --post_ids=188688 --copy-tax
```

> Changes post(s) from the post type "post" to "page" and copies all the taxonomies.

```
wp boomi migrate post-type --from=post --to=page --post_ids=188688 --tax-map=/Users/erikmitchell/bc-migration/examples/post-type-tax-map.json
```

> Changes post(s) from the post type "post" to "page" and migrates taxonomies via a custom file.

```
wp boomi migrate post-type --from=post --to=page --post_ids=188932 --meta-map=/Users/erikmitchell/bc-migration/examples/post-type-meta-map.json

```

> Changes post(s) from the post type "post" to "page" and migrates meta via a custom file.

---

## Terminus Example

```
terminus remote:wp boomi.taxcli -- boomi taxonomies update_terms content-type --csv=/code/wp-content/uploads/bc-migration/update-resource-types.csv --log=update-resource-types.log
```

> Run WP CLI term update remotely via Pantheon Terminus CLI.