# FreeCRM — Verama job sync
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import logging
from dataclasses import dataclass

import requests

logger = logging.getLogger(__name__)


class AuthError(RuntimeError):
    pass


@dataclass
class SessionAuth:
    authorization: str
    x_session: str | None


def login(
    session: requests.Session,
    base_url: str,
    email: str,
    password: str,
    recaptcha_token: str | None = None,
) -> SessionAuth:
    """Authenticate via Verama SPA login endpoint (multipart form)."""
    url = f"{base_url}/api/auth/login"
    headers: dict[str, str] = {"Accept": "application/json"}
    if recaptcha_token:
        headers["X-Recaptcha"] = recaptcha_token

    data = {
        "username": email.lower(),
        "password": password,
    }
    logger.info("Logging in to Verama as %s", email)
    response = session.post(url, data=data, headers=headers, timeout=60)

    if response.status_code >= 400:
        raise AuthError(
            f"Login failed HTTP {response.status_code}: {response.text[:500]}"
        )

    authorization = response.headers.get("Authorization") or response.headers.get(
        "authorization"
    )
    if not authorization:
        # Some gateways expose custom casing only via raw headers
        for key, value in response.headers.items():
            if key.lower() == "authorization" and value:
                authorization = value
                break

    if not authorization:
        body_preview = response.text[:300]
        raise AuthError(
            "Login succeeded but Authorization header missing. "
            f"Body preview: {body_preview}"
        )

    if not authorization.lower().startswith("bearer "):
        authorization = f"Bearer {authorization}"

    x_session = None
    for key, value in response.headers.items():
        if key.lower() == "x-session" and value:
            x_session = value
            break

    # Prefer body session id if present
    try:
        payload = response.json()
    except ValueError:
        payload = None
    if isinstance(payload, dict):
        for key in ("sessionId", "session", "xSession"):
            if payload.get(key):
                x_session = str(payload[key])
                break

    logger.info("Login OK (session=%s)", "yes" if x_session else "no")
    return SessionAuth(authorization=authorization, x_session=x_session)


def apply_auth(session: requests.Session, auth: SessionAuth) -> None:
    session.headers["Authorization"] = auth.authorization
    session.headers["Accept"] = "application/json"
    if auth.x_session:
        session.headers["X-Session"] = auth.x_session
