<!--
CONTEXT.md — the ubiquitous language of this codebase.

This file is a template. Fill one section per bounded context as you add it.
Its job is to pin down the *words* the domain uses so code, tests, docs, and
conversation all mean the same thing. Keep definitions short and add an
_Avoid_ line listing near-synonyms that cause confusion. When a term means one
thing inside the domain and another at an external boundary (wire format,
published API), say so explicitly and name where the translation happens.

Delete this comment and the example scaffolding below once a real context lands.
-->

# <Context name>

One or two sentences: what this context is responsible for, and which `src/`
module it maps to.

## Language

**<Term>**:
Definition in plain language — the concept, not its implementation.
_Avoid_: near-synonyms that should not be used for this concept.

**<Term>**:
Definition.
_Avoid_: …

### Wire / provider vocabulary (Infra only — never leaks into Domain)

**<external term>**:
Names that belong to an external system's payload. Document how they map onto
the domain terms above, and note that the Domain layer never uses these words.

## Published language (external API)

If this context exposes an API whose vocabulary deliberately differs from the
internal model, describe the published shape here and name the boundary
(typically an `Infra/Ui` mapper) where the internal model is translated into it.
