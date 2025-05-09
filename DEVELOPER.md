# Developer

Version: 0.3.0

# GitHub PR Commit and Labeling Guide for Changelog Generation

This guide explains how to commit, label, and format your PRs to generate a clean and organized changelog using GitHub Actions and `release-please`.

## Commit Messages & Labels

When working with GitHub, commit messages should follow a specific format, and appropriate labels should be applied to each PR. These labels are used to categorize changes in the changelog.

### 1. Adding a New Feature

**Commit Message:**

`git commit -m "feat: add widget shortcode for better customizability"`

**Labels to Apply:**

* Semver-Minor (since this is a new feature)
* enhancement (to indicate it’s an improvement)

**Changelog Result:**

* This commit will be categorized under Exciting New Features 🎉 in the changelog when merged into the main branch.

### 2. Fixing a Bug

**Commit Message:**

`git commit -m "fix: correct widget layout styles for mobile view"`

**Labels to Apply:**

* No labels required (unless you want to explicitly categorize it)

**Changelog Result:**

* This commit will be categorized under Other Changes in the changelog if labeled as chore, fix, or similar.
* Note: Bug fixes typically don’t need changelog labels but will still appear if they are merged into the release.

### 3. Breaking Change

If you’re removing an outdated API method or introducing a breaking change:

**Commit Message:**

```
git commit -m "feat: remove deprecated `getData()` method"
```

**Labels to Apply:**

* Semver-Major (since this is a breaking change)
* breaking-change (alternative label to indicate a breaking change)

**Changelog Result:**

* This commit will trigger the workflow to show up under Breaking Changes 🛠 in the changelog when merged into main.

### 4. Documentation Update

If you update the README, documentation, or any related content:

**Commit Message:**

`git commit -m "docs: update README with new installation steps"`

**Labels to Apply:**

* ignore-for-release (since this is just documentation)

**Changelog Result:**

* This commit will NOT appear in the changelog, as it is excluded from the release.

## How to Create a Pull Request (PR)

### Step 1: Push Your Commit to GitHub

Once you’ve committed your changes, push them to your feature branch:
git push origin your-branch-name

### Step 2: Open a Pull Request

* Go to your GitHub repo and create a new Pull Request from your feature branch to main (or your primary release branch).
* Add a title for your PR that reflects the changes made.

### Step 3: Apply Labels to the PR

* On the right-hand side of the PR page, there will be a Labels section.
* Choose the appropriate labels based on the type of change:
	
	PR Title
Labels
Resulting Changelog Section
feat: add widget shortcode
Semver-Minor, enhancement
Exciting New Features 🎉
fix: correct widget layout styles
(none)
Other Changes
feat: remove deprecated getData() API
Semver-Major, breaking-change
Breaking Changes 🛠
docs: update README with install steps
ignore-for-release
Not included in changelog

### Step 4: Merge PR and Trigger Changelog

* Once your PR is merged into main, the changelog will automatically update based on the labels.
* The release-please GitHub Action will handle the version bump, changelog entry, and tagging of the release.

## Changelog Example

📝 Changelog

**Breaking Changes 🛠**

* feat: remove deprecated getData() method

Breaking change: Removed getData() API method in favor of fetchData().

(PR: #123)

**Exciting New Features 🎉**

* feat: add new widget shortcode

Added a new [my-widget] shortcode to render a custom widget on pages.

(PR: #124)

* enhancement: improve admin UI

Refined the admin panel UI for better accessibility and responsiveness.

(PR: #125)

**Other Changes**

* docs: update installation guide
* 
Updated README and installation instructions for the latest version of the plugin.

(PR: #126)

* chore: update dependencies
* 
Updated composer.json to require latest version of phpunit.

(PR: #127)

**📅 Release Version**

* v1.2.0
* This release contains breaking changes (API removal), new features (widget shortcode), and improvements to the UI.

**Additional Notes**

* Labels are essential for categorizing changes. Use the predefined labels consistently.
* release-please will automatically generate the changelog and version bump when your PR is merged into main.
