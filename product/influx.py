import os
from influxdb_client import InfluxDBClient, Point, WritePrecision
from influxdb_client.client.write_api import SYNCHRONOUS

INFLUX_URL = "http://influxdb2:8086"
INFLUX_ORG = "docs"
INFLUX_BUCKET = "home"
INFLUX_TOKEN = os.getenv("INFLUXDB_TOKEN")

client = None
write_api = None

def init_influxdb():
    global client, write_api
    try:
        # Ensure the token is set
        if not INFLUX_TOKEN:
            raise ValueError("INFLUXDB_TOKEN is not set in the environment variables.")

        # Initialize the InfluxDB Client and Write API
        client = InfluxDBClient(url=INFLUX_URL, token=INFLUX_TOKEN, org=INFLUX_ORG)
        write_api = client.write_api(write_options=SYNCHRONOUS)
        print("Successfully connected to InfluxDB.")
    except Exception as e:
        print(f"Error connecting to InfluxDB: {e}")
        write_api = None  # Ensuring write_api is set to None in case of failure

def log_request(endpoint: str, method: str, status_code: int, duration: float):
    if write_api is not None:
        try:
            print("LOGGING TO INFLUX:", endpoint)
            # Prepare data point to log
            point = Point("api_requests")\
                .tag("endpoint", endpoint)\
                .tag("method", method)\
                .field("count", 1)\
                .field("status_code", status_code)\
                .field("duration", duration)

            # Write data to InfluxDB
            write_api.write(bucket=INFLUX_BUCKET, org=INFLUX_ORG, record=point)
        except Exception as e:
            print(f"Error writing to InfluxDB: {e}")
    else:
        print("Error: write_api is not initialized, skipping request logging.")

def log_response(request, response):
    # Calculate the duration of the request processing
    from time import time
    duration = time() - request.start_time
    log_request(request.path, method=request.method, status_code=response.status_code, duration=duration)
    return response

# Close the InfluxDB client and the WriteApi when the app is stopped
def close_influxdb():
    global client, write_api
    if write_api is not None:
        write_api.__del__()  # Force the cleanup of WriteApi
    if client is not None:
        client.close()  # Close the InfluxDB client connection

# Ensure the cleanup happens at the right moment in your application
import atexit
atexit.register(close_influxdb)