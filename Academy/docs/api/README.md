# API Specifications

This directory hosts the canonical OpenAPI specifications for the Academy Communities platform. The `communities-openapi.yaml`
file describes the complete `/api/v1` surface for communities, including membership, feeds, moderation, paywalls, geo tools, and
messaging workflows. The document is source-controlled to ensure backend, web, and mobile teams share a single contract.

## Usage

* **Backend** — Use the spec to validate controller implementations and generate request/response DTOs.
* **Web** — Generate typed API clients (e.g., with `openapi-typescript`) and sync capability matrices for admin tools.
* **Mobile** — Generate Retrofit/Dio clients or validate the manual `CommunityApiService` layer against request/response shapes.
* **QA & Compliance** — Import into Postman/Insomnia for regression suites and to provide auditors with signed API contracts.

Regenerate clients or server stubs whenever the spec changes to avoid drift. The version number in `info.version` should be
updated with semantic versioning whenever breaking changes are introduced.
