# FreeCRM — Verama job sync
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import logging
import time
from typing import Any, Iterator

import requests

from .auth import SessionAuth, apply_auth, login

logger = logging.getLogger(__name__)


class ApiError(RuntimeError):
    pass


class VeramaClient:
    def __init__(
        self,
        base_url: str,
        email: str,
        password: str,
        request_delay_sec: float = 2.0,
        list_page_size: int = 100,
        session: requests.Session | None = None,
    ) -> None:
        self.base_url = base_url.rstrip("/")
        self.email = email
        self.password = password
        self.request_delay_sec = request_delay_sec
        self.list_page_size = list_page_size
        self.session = session or requests.Session()
        self._auth: SessionAuth | None = None
        self._last_request_at = 0.0

    def authenticate(self) -> None:
        self._auth = login(self.session, self.base_url, self.email, self.password)
        apply_auth(self.session, self._auth)

    def _throttle(self) -> None:
        elapsed = time.monotonic() - self._last_request_at
        wait = self.request_delay_sec - elapsed
        if wait > 0:
            time.sleep(wait)

    def _request(self, method: str, path: str, **kwargs: Any) -> requests.Response:
        if self._auth is None:
            raise ApiError("Not authenticated")
        self._throttle()
        url = f"{self.base_url}{path}"
        response = self.session.request(method, url, timeout=60, **kwargs)
        self._last_request_at = time.monotonic()

        # Capture rotated Authorization / X-Session from responses
        auth_header = None
        for key, value in response.headers.items():
            kl = key.lower()
            if kl == "authorization" and value:
                auth_header = value
            elif kl == "x-session" and value and self._auth:
                self._auth.x_session = value
                self.session.headers["X-Session"] = value
        if auth_header and self._auth:
            if not auth_header.lower().startswith("bearer "):
                auth_header = f"Bearer {auth_header}"
            self._auth.authorization = auth_header
            self.session.headers["Authorization"] = auth_header

        return response

    def iter_job_list_pages(self) -> Iterator[list[dict[str, Any]]]:
        page = 0
        while True:
            path = (
                "/api/job-requests/v2"
                f"?page={page}&size={self.list_page_size}"
                "&query=&dedicated=false&favouritesOnly=false&recommendedOnly=false"
                "&sort=firstDayOfApplications,DESC"
            )
            logger.info("Fetching job list page %s", page)
            response = self._request("GET", path)
            if response.status_code != 200:
                raise ApiError(
                    f"List page {page} failed HTTP {response.status_code}: "
                    f"{response.text[:500]}"
                )
            payload = response.json()
            content = payload.get("content") or []
            if not isinstance(content, list):
                raise ApiError(f"Unexpected list payload on page {page}")
            yield content
            if payload.get("last") or page >= int(payload.get("totalPages", 1)) - 1:
                break
            page += 1

    def fetch_job_detail(self, job_id: int | str) -> dict[str, Any]:
        path = f"/api/job-requests/{job_id}"
        logger.info("Fetching job detail %s", job_id)
        response = self._request("GET", path)
        if response.status_code != 200:
            raise ApiError(
                f"Detail {job_id} failed HTTP {response.status_code}: "
                f"{response.text[:500]}"
            )
        payload = response.json()
        if not isinstance(payload, dict):
            raise ApiError(f"Unexpected detail payload for {job_id}")
        return payload
