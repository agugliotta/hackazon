FROM mysql:8.0

# Bake the schema and seed data into the image so init scripts
# are not bind-mounted (avoids macOS Docker Desktop permission issues).
# Files in /docker-entrypoint-initdb.d/ are executed in filename order
# on first startup when the data volume is empty.

COPY 01_schema.sql           /docker-entrypoint-initdb.d/01_schema.sql
COPY 02_migration_coupons.sql /docker-entrypoint-initdb.d/02_migration_coupons.sql
COPY 03_migration_credit_card.sql /docker-entrypoint-initdb.d/03_migration_credit_card.sql
COPY 04_demo_data.sql        /docker-entrypoint-initdb.d/04_demo_data.sql
