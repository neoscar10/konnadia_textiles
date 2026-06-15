# Kannodia Textiles Project Rules

## Stack
- Laravel 11
- Livewire 3
- Tailwind CSS
- MySQL
- Service-layer architecture
- Admin dashboard first
- Future Flutter mobile API support
- Future JWT auth with tymon/jwt-auth

## Architecture
- Keep business logic out of Livewire components.
- Use Services, Actions, DTOs, Enums, Policies, and Form-style validation.
- Future web customer portal and mobile APIs must reuse the same services.
- Do not duplicate business rules separately for API and web.
- Use database transactions for multi-step writes.
- Use policies/middleware for authorization.

## Admin CRUD UX
- All Super Admin CRUD actions must use modals.
- Delete confirmation must use a custom modal.
- Never use browser default confirm(), alert(), or prompt().
- Use consistent success/error toast feedback.
- Use loading states only on the specific action being performed.

## Design System
- Follow the Stitch admin design.
- Use Inter font.
- Use Material Symbols icons where useful.
- Main colors:
  - Deep Navy: #0F2744
  - Primary Dark: #001229
  - Gold Accent: #C89B3C / #FCCA66
  - Background: #FAF9FC / #F5F7FA
  - Surface: #FFFFFF
  - Text: #1B1C1E
  - Muted Text: #44474D
  - Border: #C4C6CE / #E2E8F0
- UI must feel modern, corporate, minimal, premium, and consistent.
- Avoid cluttered ERP-style screens.
- Use white cards, deep navy navigation, subtle shadows, thin dividers, pill badges, and generous spacing.

## Livewire Standards
- Use pagination for lists.
- Use debounced search.
- Use immediate filters.
- Keep components readable and small.
- Use shared Blade components.
- Do not duplicate form markup unnecessarily.
- Use modals for create/edit/delete/status actions.
- Dispatch events cleanly for modal/toast behavior.

## Current Scope
- Build admin dashboard foundation only.
- Do not build customer portal yet.
- Do not build APIs yet.
- Do not build ERP/Tally integration yet.
- Do not build WhatsApp notifications yet.
- Do not build online payment gateway.
- Payment model is credit-based ordering only.

## Naming
- Use “Kannodia Textiles” as the display brand unless project files prove otherwise.
- Use clear route names, model names, service names, and Livewire component names.
