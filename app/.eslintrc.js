module.exports = {
    env: {
        browser: true,
        es2021: true,
    },
    extends: [
        'eslint:recommended',
        'plugin:prettier/recommended', // Intègre Prettier dans ESLint
    ],
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
    },
    rules: {
        // === Règles Générales ===
        'no-console': 'off', // Autorisé en développement pour le débogage
        'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
        'no-var': 'error',
        'prefer-const': 'error',

        // === Règles de Style (déléguées à Prettier) ===
        'prettier/prettier': [
            'error',
            {
                endOfLine: 'auto',
            },
        ],

        // === Bonnes Pratiques ===
        'eqeqeq': ['error', 'always'],
        'curly': ['error', 'all'],
        'no-eval': 'error',
        'no-implied-eval': 'error',
        'no-throw-literal': 'error',
        'prefer-promise-reject-errors': 'error',

        // === ES6+ ===
        'prefer-arrow-callback': 'error',
        'prefer-template': 'error',
        'no-useless-constructor': 'error',

        // === Async/Await ===
        'require-await': 'warn',
        'no-async-promise-executor': 'error',
    },
    globals: {
        // Variables globales Symfony/Bootstrap
        bootstrap: 'readonly',
        Routing: 'readonly', // FOSJsRoutingBundle si utilisé
    },
};
