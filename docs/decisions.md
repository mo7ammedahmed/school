# Aether School OS — Decisions

## Decision 1: Domain-Oriented Architecture
- **Status:** Accepted
- **Rationale:** Domain boundaries prevent accidental coupling, make authorization explicit, and keep business rules testable.
- **Alternatives considered:** Flat services, hexagonal architecture.
- **Consequences:** More directories, but clearer ownership.

## Decision 2: Inertia.js as Primary CRUD Interface
- **Status:** Accepted
- **Rationale:** Reduces API surface, keeps authorization server-side, simplifies form handling.
- **Alternatives considered:** REST API + separate frontend, Livewire.
- **Consequences:** No separate mobile API yet; future API will be added via Sanctum.

## Decision 3: Spatie Permission for RBAC
- **Status:** Accepted
- **Rationale:** Mature, well-tested, integrates cleanly with Laravel policies.
- **Alternatives considered:** Custom permission system, Laravel Gates only.
- **Consequences:** Permission storage is in dedicated tables; role checks are explicit.

## Decision 4: SQLite for Local/CI, MySQL for Production
- **Status:** Accepted
- **Rationale:** Zero-config local development, production-grade MySQL 8+.
- **Alternatives considered:** SQLite only, PostgreSQL.
- **Consequences:** Must avoid SQLite-specific syntax in migrations.

## Decision 5: Private/S3-Compatible Storage
- **Status:** Accepted
- **Rationale:** Required for secure document storage, GDPR/PDPL compliance.
- **Alternatives considered:** Local filesystem only, public CDN.
- **Consequences:** All file operations go through storage abstraction.

## Decision 6: Bilingual Arabic/English with RTL
- **Status:** Accepted
- **Rationale:** Al Noor School serves Arabic and English speakers; RTL is first-class.
- **Alternatives considered:** English-only, RTL as afterthought.
- **Consequences:** All UI components must support RTL; logical CSS properties required.
