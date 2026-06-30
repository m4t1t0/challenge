# Architecture Decision Records

This directory holds **Architecture Decision Records (ADRs)**: short documents
that capture a significant decision, the context that forced it, and the
consequences we accepted. They explain *why* the code looks the way it does, so
the reasoning survives even after the people who made the call have moved on.

## When to write one

Write an ADR when a decision is **hard to reverse** or **non-obvious**: a
persistence strategy, a consistency model, a boundary between modules, a
trade-off you deliberately chose against the "default" option. Skip it for
routine, easily-changed choices.

## How to add one

1. Copy [`0000-template.md`](0000-template.md) to the next number in sequence,
   e.g. `0001-short-kebab-case-title.md`.
2. Fill in the sections. Keep it tight — an ADR is an argument, not an essay.
3. Set the `status` (`proposed` → `accepted`; later `superseded by NNNN` /
   `deprecated`). An accepted ADR is immutable; record reversals in a new ADR
   that supersedes it.
4. Cross-link related ADRs with relative links.
