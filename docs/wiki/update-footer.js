const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const WIKI_DIR = '.';
const VERSION_FILE = 'VERSION';
const FOOTER_MARKER = '<!-- VERSION-FOOTER -->';
const EXCLUDE_FILES = ['_Sidebar.md', '_Footer.md', '_Header.md'];

const version = fs.readFileSync(VERSION_FILE, 'utf8').trim();

function updateFooter(filePath) {
  if (EXCLUDE_FILES.includes(path.basename(filePath))) return;

  const content = fs.readFileSync(filePath, 'utf8');
  const footer = `\n\n${FOOTER_MARKER}\n<p align="center"><sub><em>Documentation Version: ${version}</em></sub></p>\n`;
  const withoutOldFooter = content.split(FOOTER_MARKER)[0].trim();

  fs.writeFileSync(filePath, withoutOldFooter + footer, 'utf8');
  console.log(`Updated footer in ${filePath}`);
}

function walk(dir) {
  fs.readdirSync(dir).forEach(file => {
    const fullPath = path.join(dir, file);
    if (fs.statSync(fullPath).isDirectory()) {
      walk(fullPath);
    } else if (file.endsWith('.md')) {
      updateFooter(fullPath);
    }
  });
}

function gitCommitAndPush() {
  try {
    // Stage changes
    execSync('git add .', { stdio: 'inherit' });

    // Check if there’s anything to commit
    const status = execSync('git status --porcelain').toString();
    if (!status.trim()) {
      console.log('✅ No changes to commit.');
      return;
    }

    // Commit
    execSync(`git commit -m "Update version footers to ${version}"`, { stdio: 'inherit' });

    // Optional: Push changes
    // execSync('git push', { stdio: 'inherit' });

    console.log('✅ Changes committed.');
  } catch (err) {
    console.error('❌ Git operation failed:', err.message);
  }
}

// Run everything
walk(WIKI_DIR);
gitCommitAndPush();