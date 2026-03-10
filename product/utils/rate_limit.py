from .redis_client import r

def rate_limit(ip, limit=20, window=60):
    key = f"rate_limit:{ip}"

    current = r.get(key)

    if current and int(current) >= limit: return False
    
    r.incr(key)

    if not current: r.expire(key, window)

    return True
