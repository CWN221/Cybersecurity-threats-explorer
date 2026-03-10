from flask_sqlalchemy import SQLAlchemy
import psycopg2
from psycopg2 import sql
from config import POSTGRES_USER, POSTGRES_PASSWORD, POSTGRES_DB, POSTGRES_HOST, POSTGRES_PORT

db = SQLAlchemy()


def create_database_if_not_exists():
    # Connect to Postgres DB
    conn = psycopg2.connect(database='postgres', user=POSTGRES_USER, password=POSTGRES_PASSWORD, host=POSTGRES_HOST, port=POSTGRES_PORT)
    conn.autocommit = True
    cursor = conn.cursor()

    try:
        # Check if DB exists
        cursor.execute("SELECT 1 FROM pg_catalog.pg_database WHERE datname = %s;", (POSTGRES_DB,))
        exists = cursor.fetchone()

        # If DB doesn't exist, create it
        if not exists:
            cursor.execute(sql.SQL("CREATE DATABASE {}").format(sql.Identifier(POSTGRES_DB)))
            print(f"Database {POSTGRES_DB} created successfully")
    except Exception as ex:
        print(f"Error creating database: {ex}")
    finally:
        cursor.close()
        conn.close()

# Run the database creation check
create_database_if_not_exists()