# Architecture: orm (FuelPHP ORM)

## Purpose

The FuelPHP ORM (Object-Relational Mapper) provides Active-Record style data access for FuelPHP applications. Supports relationships (has_one, has_many, belongs_to, many_many), soft deletes, temporal models (version history), nested sets, and an observer pattern for lifecycle hooks.

## Directory Structure

```
classes/
  model.php           — Base Model class: find, save, delete, property access, relationship loading
  relation.php        — Abstract base for all relation types
  belongsto.php       — Belongs-to relation (foreign key on this model)
  hasone.php          — Has-one relation (foreign key on related model)
  hasmany.php         — Has-many relation (foreign key on related model)
  manymany.php        — Many-to-many relation (pivot table)
  query.php           — Query builder: where, order, limit, join, get, count
  observer.php        — Observer interface for lifecycle events (before_save, after_delete, etc.)
  observer/
    createdat.php     — Auto-sets created_at timestamp on insert
    updatedat.php     — Auto-sets updated_at timestamp on update
    validation.php    — Runs validation before save
    slug.php          — Auto-generates URL slugs
    typing.php        — Type-casts properties to declared types
    self.php          — Observer that the model itself acts as
  query/
    soft.php          — Excludes soft-deleted rows by default
    temporal.php      — Filters to a specific revision timestamp
  model/
    soft.php          — Model trait for soft-delete (deleted_at column, restore())
    temporal.php      — Model trait for temporal versioning (revision tracking)
    nestedset.php     — Model trait for nested set tree structure
```

## Key Design Decisions

- **Active Record pattern**: Each model instance represents one database row; class methods (`find()`, `query()`) are static factory methods
- **Observer chain**: Multiple observers can be attached to a model; they fire in registration order on lifecycle events
- **Relation lazy loading**: Relations are loaded on first property access; a `$_related` cache prevents redundant queries
- **Temporal / soft-delete via traits**: These behaviors are opt-in by extending the appropriate sub-model class (e.g., `Model_Soft`) rather than configuring the base model

## Extension Points

- Extend `Observer` and register it on a model via `$_observers` to add custom lifecycle behavior
- Define `$_has_many`, `$_belongs_to`, etc. as static arrays on your model to declare relations
- Extend `Model_Nestedset` for hierarchical (tree) data

## Dependency Flow

```
Model::find($id)
  → Query builder (Query class)
  → Database (FuelPHP DB package)
  → Result hydration (Model instances)
  → Observer::after_find() notifications
```
