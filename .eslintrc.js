// SheShield — ESLint Configuration
// Enforces code quality standards across all JavaScript files

module.exports = {
  env: {
    browser: true,
    es2021: true,
    node: true,
  },
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module',
  },
  rules: {
    // Code Quality
    'no-unused-vars': 'warn',
    'no-undef': 'warn',
    'no-console': 'off',
    'no-debugger': 'error',

    // Best Practices
    'eqeqeq': ['warn', 'always'],
    'no-eval': 'error',
    'no-implied-eval': 'error',
    'no-alert': 'warn',

    // Style
    'semi': ['warn', 'always'],
    'quotes': ['warn', 'single', { 'allowTemplateLiterals': true }],
    'indent': ['warn', 2, { 'SwitchCase': 1 }],
    'no-trailing-spaces': 'warn',
    'comma-dangle': ['warn', 'always-multiline'],
  },
  ignorePatterns: [
    'node_modules/',
    'infrastructure/',
    'vendor/',
    '*.min.js',
    'src/output.css',
  ],
};
