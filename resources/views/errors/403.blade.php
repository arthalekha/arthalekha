<x-layouts.error code="403" title="Forbidden" :message="$exception->getMessage() ?: \"You don't have permission to access this page.\"" />
