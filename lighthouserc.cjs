/** @type {import('@lhci/cli').Config} */
module.exports = {
  ci: {
    collect: {
      startServerCommand: 'php artisan serve --host=127.0.0.1',
      startServerReadyPattern: 'Server running',
      url: [
        'http://127.0.0.1:8000/',
        'http://127.0.0.1:8000/login',
        'http://127.0.0.1:8000/batches',
      ],
      numberOfRuns: 1,
    },
    assert: {
      assertions: {
        'categories:performance': ['warn', { minScore: 0.85 }],
        'categories:accessibility': ['error', { minScore: 0.90 }],
      },
    },
    upload: {
      target: 'temporary-public-storage',
    },
  },
};
