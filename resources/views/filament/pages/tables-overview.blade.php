<x-filament-panels::page
    x-init="
        // Listen for reload-page event to refresh the page
        window.addEventListener('reload-page', () => {
            location.reload();
        });
    }
>
    x-data="{
        tableParam: {{ request('table') ? request('table') : 'null' },
    }"
    x-init="
        if (tableParam) { setTimeout(() => { const tables = {{ Js::from($this->tables) }}; const table = tables.find(t => t.id == tableParam); window.dispatchEvent(new CustomEvent('showOrderDetails', { detail: { tableId: tableParam, tableName: table ? table.name : 'Meja' }}) }, 100) }

        // Listen for reload-page event to refresh the page
        window.addEventListener('reload-page', () => {
            location.reload();
        });
    "
>
