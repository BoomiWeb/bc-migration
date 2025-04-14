# WP CLI Commands

A collection of examples for new `wp boomi taxonomies` CLI commands to help rename, merge, delete, validate, and update taxonomy terms.

---

## Rename Taxonomies

### Dry Run

```bash
wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --dry-run
```

> Simulates renaming terms from a CSV without applying changes.

### Log Output

```bash
wp boomi taxonomies rename --file=/Users/erikmitchell/bc-migration/src/examples/tax-rename.csv --log=rename-terms.log
```

> Logs rename actions to a file for review.

### Rename Single Term

```bash
wp boomi taxonomies rename industries "M&A" "Mergers & Acquisitions" --new-slug="mergers-acquisitions" --dry-run --log=rename.log
```

> Dry-run to rename a single term within a taxonomy, specifying a new slug.

```bash
wp boomi taxonomies rename industries "Mergers. & Acquisitions" "Mergers & Acquisitions"
```

> Renames a single term with live execution (no dry-run).

---

## Merge Taxonomies

### Single Merge

```bash
wp boomi taxonomies merge products "Foo Boo|Bar Foo" "Foo Bar" --post-type=blog --delete-old --log=merge.log
```

> Merges multiple terms into one, deletes old ones, and logs results.

```bash
wp boomi taxonomies merge products "Foo Boo|Bar Foo" "Foo Bar" --post-type=blog
```

> Same as above but without deletion or logging.

### Batch Merge

```bash
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv --log=merge.log
```

> Merges terms in bulk from CSV and logs output.

```bash
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv --delete-old
```

> Bulk merge with deletion of old terms.

```bash
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv
```

> Basic bulk merge with no log or deletion flags.

### Dry Run

```bash
wp boomi taxonomies merge products "Foo Boo|Bar Foo" "Foo Bar" --post-type=blog --dry-run
```

> Test a single-term merge without applying changes.

```bash
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv --log=merge.log --dry-run
```

> Simulates a batch merge and logs the expected result.

---

## Delete Terms

### Delete Single Term

```bash
wp boomi taxonomies delete industries "Foo Boo|Bar Foo" --log=delete.log --dry-run
```

> Simulates deletion of a single term with logging.

```bash
wp boomi taxonomies delete industries "Foo Boo|Bar Foo" --log=delete.log
```

> Deletes a single term from a taxonomy and logs the action.

### Batch Delete

```bash
wp boomi taxonomies delete --file=/Users/erikmitchell/bc-migration/src/examples/tax-delete.csv --log=delete.log
```

> Deletes multiple terms from a CSV file with logging.

```bash
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv
```

> (Likely misfiled in the delete section; this is actually a merge command.)

### Dry Run

```bash
wp boomi taxonomies delete industries "Foo Boo|Bar Foo" --log=delete.log --dry-run
```

> Dry run for single term deletion.

```bash
wp boomi taxonomies delete --file=/Users/erikmitchell/bc-migration/src/examples/tax-delete.csv --log=delete.log --dry-run
```

> Dry run for batch deletion from a file.

---

## Error Examples

```bash
wp boomi taxonomies merge products "A|B" "C" --post-type=invalid_post_type
```

> Invalid post type should trigger an error.

```bash
wp boomi taxonomies merge invalid-taxonomy "A|B" "C"
```

> Invalid taxonomy should also return an error.

```bash
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge.csv --post-type=invalid_post_type
```

> Post type in file context is invalid.

```bash
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/tax-merge-bad.csv
```

> Malformed CSV file—should fail.

```bash
wp boomi taxonomies merge --file=/Users/erikmitchell/bc-migration/src/examples/invalid-file.csv
```

> File doesn’t exist or is inaccessible.

---

## Testing (Misc)

```bash
wp boomi taxonomies delete industries "Foo Boo|Bar Foo" --log=delete.log
```

> Confirming deletion command works as expected.

```bash
wp boomi taxonomies delete --file=/Users/erikmitchell/bc-migration/src/examples/tax-delete.csv --log=delete.log
```

> Testing file-based deletions.

```bash
wp boomi taxonomies delete industries "Foo Boo|Bar Foo" --log=delete.log --dry-run
```

> Test dry run of deletion logic.

---

## Term Validation

### Single Term Input

```bash
wp boomi taxonomies term-validator category --terms="News,Updates"
```

> Validates terms against existing ones in the `category` taxonomy.

```bash
wp boomi taxonomies term-validator category --terms="News,Updates" --field=name
```

> Uses term names instead of slugs for validation.

### Bulk Term Input

```bash
wp boomi taxonomies term-validator category --file=/Users/erikmitchell/bc-migration/src/examples/categories.csv --delete --dry-run --log=term-validation.log
```

> Simulated validation and deletion of invalid terms from CSV.

```bash
wp boomi taxonomies term-validator category --file=/Users/erikmitchell/bc-migration/src/examples/categories.csv --log=term-validation.log
```

> Log-only run for validating terms from a file.

```bash
wp boomi taxonomies term-validator category --file=/Users/erikmitchell/bc-migration/src/examples/categories.csv --log=term-validation.log --delete
```

> Validates and deletes invalid terms in bulk.

---

## Term Updater (Parent > Child Terms)

### Single String Input

```bash
wp boomi taxonomies update_terms content-type "News & Updates > Press Release, News"
```

> Moves "Press Release" and "News" under parent "News & Updates".

```bash
wp boomi taxonomies update_terms content-type "News & Updates > Press Release, News" --log=update-terms.log
```

> Same as above with logging.

### CSV Input

```bash
wp boomi taxonomies update_terms content-type --csv=/Users/erikmitchell/bc-migration/src/examples/update-terms.csv
```

> Updates taxonomy hierarchy from a CSV file.

```bash
wp boomi taxonomies update_terms content-type --csv=/Users/erikmitchell/bc-migration/src/examples/update-terms.csv --dry-run
```

> Dry run to validate the changes before applying.

```bash
wp boomi taxonomies update_terms content-type --csv=/Users/erikmitchell/bc-migration/src/examples/update-terms.csv --log=update-terms.log
```

> Full run with logging for auditing.

### Dry Run (Single Input)

```bash
wp boomi taxonomies update_terms content-type "News & Updates > Press Release, News" --dry-run
```

> Simulate the update for a single line input before running live.

---

## Terminus Example

```bash
terminus remote:wp boomi.taxcli -- boomi taxonomies update_terms content-type --csv=/code/wp-content/uploads/bc-migration/update-resource-types.csv --log=update-resource-types.log
```

> Run WP CLI term update remotely via Pantheon Terminus CLI.