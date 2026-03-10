from flask import Flask, request
from influx import init_influxdb
from flask_sqlalchemy import SQLAlchemy
from flask_restful import Resource, Api
from config import SQLALCHEMY_DATABASE_URI
from utils.db import db
from routes.threat_routes import Threats, ThreatResource
from influx import log_request, log_response
from time import time

def create_app():
    app = Flask(__name__)

    init_influxdb()
    
    # Config
    app.config["SQLALCHEMY_DATABASE_URI"] = SQLALCHEMY_DATABASE_URI
    app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False

    # Initialize extensions
    db.init_app(app)

    # Create tables
    with app.app_context():
        db.create_all()

    # REST API
    api = Api(app)

    # Log every request to InfluxDB
    @app.before_request
    def start_timer():
        request.start_time = time()

    @app.after_request
    def after_request(response):
        log_response(request, response)
        return response

    api.add_resource(Threats, '/threats')
    api.add_resource(ThreatResource, "/threats/<int:id>")

    return app


app = create_app() 

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
