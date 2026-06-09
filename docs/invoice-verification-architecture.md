# Sistem Verifikasi Tagihan dan Dokumen Pembayaran

## Folder / Module Structure

```text
app/
  Modules/
    InvoiceVerification/
      Actions/
      Domain/
        Enums/
        Models/
      Http/
        Controllers/
        Requests/
      Jobs/
      Policies/
      Services/
        Contracts/
        Implementations/
resources/views/
  invoice-verification/
    dashboard/
    transactions/
    approvals/
    generated-documents/
    documents/
    vendor-reviews/
    ppa-verification-sheets/
    accounting-verifications/
    numbering-registers/
    compiled-documents/
    archive/
    master-data/
    audit-logs/
    components/
    partials/
```

## Domain Notes

- `transactions` is the aggregate root for workflow, documents, approval, verification, numbering register, compilation, and archive.
- `generated_documents` is reserved for system-controlled documents and stays separate from user/vendor upload records.
- `transaction_documents` stores every uploaded file as a separate versioned row with `is_latest` for fast inbox and audit queries.
- `ppa_verification_sheets` and `ppa_verification_sheet_items` model checklist data as structured records, not upload-only files.
- `compiled_documents` stores final bundle metadata, while `compiled_document_items` preserves bundle order for traceability and future API/mobile consumption.

## Approval and Verification Rules

- Initial approval flow is bootstrapped from `approval_flows` when a transaction is created and its initial system document is generated.
- Vendor documents enter `UNDER_REVIEW` and must pass `vendor_document_reviews` before accounting can see them.
- Internal documents uploaded by `USER_DIVISI` are immediately available to accounting.
- Accounting verification works per document through `accounting_verification_items`, enabling partial revision instead of transaction-wide rollback.
- PPA checklist mismatches are derived by comparing structured checklist items with actual latest uploaded documents.

## LDAP Ready Integration

- `users`, `departments`, and `divisions` include LDAP identifiers and sync timestamps.
- `LdapDirectorySynchronizer` is the service contract for future LDAP adapter implementation.
- `SyncLdapDirectoryJob` is the queue-ready integration hook for asynchronous sync.

## Document Generation and Compilation

- `GeneratedDocumentService` creates initial system-controlled documents and binds approval records.
- `PpaVerificationSheetPdfGenerator` is a dedicated adapter seam for future real PDF output.
- `DocumentCompiler` is the compilation contract; current adapter writes placeholder content so orchestration, metadata persistence, and archive flow are already wired.
- `GenerateCompiledDocumentJob` and `GeneratePpaVerificationSheetPdfJob` are queue-ready wrappers for heavy processes.

## Indexing and Performance Considerations

- Inbox and queue workloads are optimized with composite indexes such as:
  - `transactions(status, current_step, created_at)`
  - `transactions(transaction_type_id, status)`
  - `transaction_documents(transaction_id, document_type_id, is_latest)`
  - `approval_transactions(approver_user_id, status, created_at)`
  - `accounting_verification_items(accounting_verification_id, status)`
- Duplicate invoice prevention is enforced at application validation and database level through unique `(vendor_id, invoice_number)` on `invoice_metadata`.
- `audit_logs` supports high-volume reference lookups with `(transaction_id, created_at)` and `(reference_type, reference_id)`.
- `numbering_register` keeps dedicated indexes on `invoice_number`, `memo_number`, `contract_number`, and `generated_at` for search-heavy archive/report screens.
- ULIDs are used for transaction-centric domain tables to keep identifiers API-safe and horizontally scalable.
