from utils.db import db

# Database Model
class Threat(db.Model):
    __tablename__ = "threats"

    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(150), nullable=False)
    type = db.Column(db.String(150), nullable=False)
    severity = db.Column(db.String(20), nullable=False)

    description = db.Column(db.Text)
    impact = db.Column(db.Text)
    mitigation = db.Column(db.Text)

    category = db.Column(db.String(100))
    attack_vector = db.Column(db.String(100))

    source = db.Column(db.String(150))
    reference_url = db.Column(db.String(300))
   
    def to_dict(self):
        return {
            "Id": self.id,
            "Name": self.name,
            "Type": self.type,
            "Severity": self.severity,
            "Description": self.description,
            "Impact": self.impact,
            "Mitigation": self.mitigation,
            "Category": self.category,
            "Attack_vector": self.attack_vector,
            "Source": self.source,
            "Reference_URL": self.reference_url
        }

  