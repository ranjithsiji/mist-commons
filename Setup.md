# GitHub Repository Setup Guide

This guide will help you create a complete GitHub repository for the Wikimedia Commons Dashboard.

## Method 1: Manual Setup (Recommended)

### Step 1: Create Project Directory

```bash
mkdir wikimedia-commons-dashboard
cd wikimedia-commons-dashboard
```

### Step 2: Initialize Git

```bash
git init
```

### Step 3: Create All Files

Copy all the files from the artifacts into your project directory with this structure:

```
wikimedia-commons-dashboard/
├── api/
│   ├── categories.php
│   ├── categories.json
│   ├── dashboard.php
│   └── config.php
├── components/
│   ├── CoverPage.vue
│   ├── StatsCards.vue
│   ├── PhotoMap.vue
│   ├── DashboardCharts.vue
│   └── ContributorsTable.vue
├── composables/
│   ├── useApi.js
│   └── useData.js
├── public/
├── App.vue
├── main.js
├── style.css
├── index.html
├── vite.config.js
├── tailwind.config.js
├── postcss.config.js
├── package.json
├── .htaccess
├── nginx.conf
├── .gitignore
├── README.md
├── QUICKSTART.md
├── PROJECT_STRUCTURE.md
└── deploy.sh
```

### Step 4: Create .gitignore

```bash
cat > .gitignore << 'EOF'
node_modules/
dist/
cache/*.json
.env
.env.local
.vscode/
.idea/
*.log
.DS_Store
EOF
```

### Step 5: Initial Commit

```bash
git add .
git commit -m "Initial commit: Wikimedia Commons Analytics Dashboard"
```

### Step 6: Create GitHub Repository

1. Go to https://github.com/new
2. Repository name: `wikimedia-commons-dashboard`
3. Description: `A modular Vue.js dashboard for analyzing Wikimedia Commons photo contests`
4. Choose Public or Private
5. **DO NOT** initialize with README (we already have one)
6. Click "Create repository"

### Step 7: Push to GitHub

```bash
git remote add origin https://github.com/YOUR_USERNAME/wikimedia-commons-dashboard.git
git branch -M main
git push -u origin main
```

---

## Method 2: Using Automated Script

### Step 1: Download and Run Setup Script

```bash
# Download or create the setup script
curl -O https://your-url/setup.sh
# OR manually create setup.sh with the content from the artifact

# Make it executable
chmod +x setup.sh

# Run it
./setup.sh wikimedia-commons-dashboard
```

### Step 2: Copy Component Files

```bash
cd wikimedia-commons-dashboard

# Create component files manually or copy from artifacts
# You'll need to create each .vue and .js file
```

### Step 3: Initialize Git and Push

```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USERNAME/wikimedia-commons-dashboard.git
git branch -M main
git push -u origin main
```

---

## Method 3: Create Complete Project with One Script

Create a file called `create-project.sh`:

```bash
#!/bin/bash

PROJECT="wikimedia-commons-dashboard"
GITHUB_USER="YOUR_GITHUB_USERNAME"

echo "Creating project: $PROJECT"

# Create directory
mkdir -p $PROJECT
cd $PROJECT

# Initialize git
git init

# Create directory structure
mkdir -p api components composables public cache

# Create package.json
cat > package.json << 'PKGJSON'
{
  "name": "wikimedia-commons-dashboard",
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "vue": "^3.4.21",
    "leaflet": "^1.9.4",
    "chart.js": "^4.4.1"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.4",
    "vite": "^5.1.4",
    "autoprefixer": "^10.4.17",
    "postcss": "^8.4.35",
    "tailwindcss": "^3.4.1"
  }
}
PKGJSON

# Create .gitignore
cat > .gitignore << 'GITIGNORE'
node_modules/
dist/
cache/*.json
.env
.env.local
.vscode/
.idea/
*.log
.DS_Store
GITIGNORE

# Create README
cat > README.md << 'README'
# Wikimedia Commons Analytics Dashboard

A modular Vue.js dashboard for analyzing Wikimedia Commons photo contests.

## Quick Start

\`\`\`bash
npm install
npm run dev
\`\`\`

## Features

- 📊 Multiple photo contests support
- 🗺️ Interactive maps
- 📈 Charts and statistics
- 💾 Intelligent caching
- 📱 Responsive design

## Documentation

See docs folder for detailed documentation.
README

echo ""
echo "✓ Project structure created!"
echo ""
echo "Next steps:"
echo "1. Copy all component files to their directories"
echo "2. npm install"
echo "3. git add ."
echo "4. git commit -m 'Initial commit'"
echo "5. git remote add origin https://github.com/$GITHUB_USER/$PROJECT.git"
echo "6. git push -u origin main"
echo ""
```

Run it:

```bash
chmod +x create-project.sh
./create-project.sh
```

---

## Quick File Copy Guide

### Copy files in this order:

1. **Root files:**
   ```bash
   # Copy: package.json, vite.config.js, tailwind.config.js, 
   # postcss.config.js, index.html, main.js, style.css, 
   # App.vue, .htaccess, nginx.conf
   ```

2. **API files:**
   ```bash
   # Copy to api/: categories.php, categories.json, 
   # dashboard.php, config.php
   ```

3. **Components:**
   ```bash
   # Copy to components/: CoverPage.vue, StatsCards.vue, 
   # PhotoMap.vue, DashboardCharts.vue, ContributorsTable.vue
   ```

4. **Composables:**
   ```bash
   # Copy to composables/: useApi.js, useData.js
   ```

5. **Documentation:**
   ```bash
   # Copy: README.md, QUICKSTART.md, PROJECT_STRUCTURE.md
   ```

---

## Verify Your Setup

```bash
# Check file structure
tree -L 2

# Install dependencies
npm install

# Test development server
npm run dev

# Build for production
npm run build
```

---

## GitHub Repository Best Practices

### 1. Add Topics/Tags

In your GitHub repo settings, add topics:
- `vue`
- `wikimedia`
- `wikimedia-commons`
- `dashboard`
- `analytics`
- `vite`
- `tailwindcss`
- `chartjs`
- `leaflet`

### 2. Create GitHub Actions (Optional)

Create `.github/workflows/build.yml`:

```yaml
name: Build and Test

on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    - uses: actions/setup-node@v3
      with:
        node-version: '18'
    - run: npm install
    - run: npm run build
```

### 3. Add LICENSE

Choose MIT License:

```bash
cat > LICENSE << 'EOF'
MIT License

Copyright (c) 2024 Your Name

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
EOF
```

### 4. Add Screenshots

Create a `screenshots/` folder and add:
- Homepage screenshot
- Dashboard screenshot
- Map screenshot
- Charts screenshot

Reference them in README.md

---

## Troubleshooting

### Problem: "Permission denied" when pushing

**Solution:**
```bash
# Use SSH instead of HTTPS
git remote set-url origin git@github.com:YOUR_USERNAME/wikimedia-commons-dashboard.git
```

### Problem: Files too large

**Solution:**
```bash
# Check file sizes
find . -type f -size +50M

# Add large files to .gitignore
echo "large-file.zip" >> .gitignore
```

### Problem: Merge conflicts

**Solution:**
```bash
# Pull first, then push
git pull origin main --rebase
git push origin main
```

---

## Share Your Repository

Once published, share your repo:

1. **Wikimedia Commons**: Share on talk pages
2. **Social Media**: Tweet with #Wikimedia hashtag
3. **Developer Forums**: Post on Vue.js, Wikimedia forums
4. **Documentation**: Link from Wikimedia tools page

---

## Repository URL Examples

After setup, your repo will be at:

- **HTTPS**: `https://github.com/YOUR_USERNAME/wikimedia-commons-dashboard`
- **SSH**: `git@github.com:YOUR_USERNAME/wikimedia-commons-dashboard.git`
- **Clone**: `git clone https://github.com/YOUR_USERNAME/wikimedia-commons-dashboard.git`

---

## Need Help?

If you encounter issues:

1. Check GitHub's documentation: https://docs.github.com
2. Verify all files are created correctly
3. Ensure `package.json` is valid JSON
4. Test `npm install` works before committing
5. Check `.gitignore` is working: `git status`

Good luck with your project! 🚀