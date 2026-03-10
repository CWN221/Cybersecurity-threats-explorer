from flask import request
from flask_restful import Resource, Api
from models.threat_model import Threat
from utils.db import db
from utils.rate_limit import rate_limit
from utils.redis_client import r
import json


# API resources
class Threats(Resource):
    # GET
    def get(self):   

        ip = request.remote_addr
        if not rate_limit(ip):
            return { "error": "Too many requests" }, 429

        cached_threats = r.get('threats_list')     

        if cached_threats:
            return json.loads(cached_threats), 200
        
        if Threat.query.count() == 0:
            db.session.add(Threat(
                name="SQL Injection",
                type="Injection",
                severity="High",
                description="SQL injection is a code injection technique that exploits a vulnerability in an application's software by manipulating SQL queries.",
                impact="An attacker can access sensitive data, modify records, delete data, or execute administrative operations on the database.",
                mitigation="Use parameterized queries, prepared statements, and ORM-based query construction to prevent direct manipulation of SQL queries.",
                category="Injection Attack",
                attack_vector="Web Application Vulnerability - Direct SQL query manipulation via user inputs.",
                source="OWASP",
                reference_url="https://owasp.org/www-community/attacks/SQL_Injection"
            ))
            db.session.commit()

        threats = Threat.query.all()        
        result =  {"threats" : [t.to_dict() for t in threats]}      

        r.set('threats_list', json.dumps(result), ex=60) # cache for 60 seconds 
        return result, 200
  

    # POST
    def post(self):
        data = request.get_json()

        threat = Threat(
            name = data.get("name"),
            type = data.get("type"),
            severity = data.get("severity"),
            description = data.get("description"),
            impact = data.get("impact"),
            mitigation = data.get("mitigation"),
            category = data.get("category"),
            attack_vector = data.get("attack_vector"),
            source = data.get("source"),
            reference_url = data.get("reference_url")
        )

        db.session.add(threat)
        db.session.commit()

        r.delete("threats_list")

        return {
            "message" : "Threat added successfully",
            "threat": threat.to_dict()
        }, 201
    

class ThreatResource(Resource):

    # GET /threats/<id>
    def get(self, id):

        ip = request.remote_addr
        if not rate_limit(ip): return { "error": "Too many request"}, 429

        cache_key = f"threats:{id}"
        cached = r.get(cache_key)

        if cached:
            return json.loads(cached), 200
        
        threat = Threat.query.get_or_404(id)
        threat_data =  threat.to_dict()

        r.set(cache_key, json.dumps(threat_data), ex=120)
        return threat_data, 200
    
    # PUT /threats/<id>
    def put(self, id):
        data = request.get_json()

        threat = Threat.query.get_or_404(id)
        threat.name = data.get("name", threat.name)
        threat.type = data.get("type", threat.type)
        threat.severity = data.get("severity", threat.severity)
        threat.description = data.get("description", threat.description)
        threat.impact = data.get("impact", threat.impact)
        threat.mitigation = data.get("mitigation", threat.mitigation)
        threat.category = data.get("category", threat.category)
        threat.attack_vector = data.get("attack_vector", threat.attack_vector)
        threat.source = data.get("source", threat.source)
        threat.reference_url = data.get("reference_url", threat.reference_url)

        db.session.commit()

        r.delete("threats_list")
        r.delete(f"threat:{id}")
        
        return { 
            "message" : "Threat updated",
            "threat" : threat.to_dict()
        }, 200
    
    # DELETE /threats/<id>
    def delete(self, id):
        threat = Threat.query.get_or_404(id)

        db.session.delete(threat)
        db.session.commit()
        
        r.delete(f"threat:{id}")

        return { "message" : "Threat deleted"}, 200