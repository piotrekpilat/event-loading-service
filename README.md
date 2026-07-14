# Event Loader

## Requirements
- Docker
- Make

## How to run
Run the following command to build the environment, install dependencies, and start the application:
```bash
make start
```

## Architecture Notes & Deduplication Limitations
The current implementation uses Redis with a TTL for deduplication, so long-term data integrity in a production environment ultimately relies on a `UNIQUE` constraint in the main database to prevent duplicate inserts if the cache is lost.
