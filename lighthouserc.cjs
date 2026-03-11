/** @type {import('@lhci/cli').Config} */
module.exports = {
  ci: {
    collect: {
      startServerCommand: 'php artisan serve --host=127.0.0.1',
      url: [
        'http://127.0.0.1:8000/',
        'http://127.0.0.1:8000/login',
        'http://127.0.0.1:8000/batches',
      ],
      numberOfRuns: 1,
    },
    assert: {
      assertions: {
        'categories:performance': ['error', { minScore: 0.95 }],
        'categories:accessibility': ['error', { minScore: 0.95 }],
      },
    },
    upload: {
      target: 'temporary-public-storage',
    },
  },
};
