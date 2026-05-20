<template>
    <div class="flex flex-col">
        <div class="ns-box flex flex-col">
            <div class="border-b ns-box-body p-3 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <p class="text-sm">{{ __( 'Arraste as categorias para reorganizar. Salve para aplicar a ordem no Kiosk, no PDV e no admin.' ) }}</p>
                <div class="flex gap-2">
                    <ns-button type="info" @click="saveOrder()" :disabled="saving">
                        <span v-if="saving">{{ __( 'Salvando...' ) }}</span>
                        <span v-else>{{ __( 'Salvar Ordem' ) }}</span>
                    </ns-button>
                </div>
            </div>

            <div class="ns-box-body p-3">
                <ul class="flex flex-col gap-2">
                    <li
                        v-for="(parent, parentIdx) in rootList"
                        :key="parent.id"
                        class="border rounded ns-box-body"
                        draggable="true"
                        @dragstart="onDragStart('root', parentIdx, null, $event)"
                        @dragover.prevent
                        @drop.prevent="onDrop('root', parentIdx, null)"
                    >
                        <div class="p-2 flex items-center justify-between cursor-move select-none">
                            <div class="flex items-center gap-2">
                                <span class="text-xs opacity-60">⋮⋮</span>
                                <strong>{{ parent.name }}</strong>
                                <span class="text-xs opacity-50">(posição {{ parentIdx }})</span>
                            </div>
                            <button
                                v-if="parent.children && parent.children.length"
                                class="text-sm underline"
                                @click="toggleExpanded(parent.id)"
                            >
                                {{ expanded[ parent.id ] ? __( 'Ocultar subcategorias' ) : __( 'Mostrar subcategorias' ) + ' (' + parent.children.length + ')' }}
                            </button>
                        </div>
                        <ul
                            v-if="parent.children && parent.children.length && expanded[ parent.id ]"
                            class="flex flex-col gap-1 px-4 pb-2"
                        >
                            <li
                                v-for="(child, childIdx) in parent.children"
                                :key="child.id"
                                class="border rounded p-2 ns-box flex items-center gap-2 cursor-move select-none"
                                draggable="true"
                                @dragstart.stop="onDragStart('child', childIdx, parent.id, $event)"
                                @dragover.prevent
                                @drop.prevent.stop="onDrop('child', childIdx, parent.id)"
                            >
                                <span class="text-xs opacity-60">⋮⋮</span>
                                <span>{{ child.name }}</span>
                                <span class="text-xs opacity-50">(posição {{ childIdx }})</span>
                            </li>
                        </ul>
                    </li>
                </ul>
                <p v-if="rootList.length === 0" class="text-sm opacity-60 italic">
                    {{ __( 'Nenhuma categoria cadastrada.' ) }}
                </p>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';

interface ReorderChild {
    id: number;
    name: string;
    position: number;
    parent_id: number;
}

interface ReorderRoot {
    id: number;
    name: string;
    position: number;
    children: ReorderChild[];
}

export default {
    name: 'ns-categories-reorder',
    props: {
        rawCategories: {
            type: [ Array, String ],
            default: () => [],
        },
    },
    data() {
        return {
            rootList: [] as ReorderRoot[],
            expanded: {} as Record<number, boolean>,
            dragInfo: null as null | { scope: 'root' | 'child'; index: number; parentId: number | null },
            saving: false,
        };
    },
    mounted() {
        const raw = typeof this.rawCategories === 'string'
            ? JSON.parse( this.rawCategories )
            : this.rawCategories;

        this.rootList = ( raw || [] ).map( ( parent: ReorderRoot ) => ( {
            ...parent,
            children: ( parent.children || [] ).map( ( child: ReorderChild ) => ( { ...child } ) ),
        } ) );
    },
    methods: {
        __,
        toggleExpanded( id: number ) {
            this.expanded[ id ] = ! this.expanded[ id ];
        },
        onDragStart( scope: 'root' | 'child', index: number, parentId: number | null, event: DragEvent ) {
            this.dragInfo = { scope, index, parentId };
            if ( event.dataTransfer ) {
                event.dataTransfer.effectAllowed = 'move';
            }
        },
        onDrop( scope: 'root' | 'child', targetIndex: number, parentId: number | null ) {
            if ( ! this.dragInfo ) {
                return;
            }
            if ( this.dragInfo.scope !== scope ) {
                this.dragInfo = null;
                return;
            }

            if ( scope === 'root' ) {
                const moved = this.rootList.splice( this.dragInfo.index, 1 )[ 0 ];
                this.rootList.splice( targetIndex, 0, moved );
            } else {
                if ( this.dragInfo.parentId !== parentId ) {
                    this.dragInfo = null;
                    return;
                }
                const parent = this.rootList.find( ( p ) => p.id === parentId );
                if ( ! parent ) {
                    this.dragInfo = null;
                    return;
                }
                const moved = parent.children.splice( this.dragInfo.index, 1 )[ 0 ];
                parent.children.splice( targetIndex, 0, moved );
            }

            this.dragInfo = null;
        },
        buildPayload() {
            const payload: { id: number; position: number }[] = [];
            this.rootList.forEach( ( parent, index ) => {
                payload.push( { id: parent.id, position: index } );
                ( parent.children || [] ).forEach( ( child, childIdx ) => {
                    payload.push( { id: child.id, position: childIdx } );
                } );
            } );
            return payload;
        },
        saveOrder() {
            const payload = this.buildPayload();
            this.saving = true;

            nsHttpClient.post( '/api/categories/reorder', { categories: payload } )
                .subscribe( {
                    next: ( result: any ) => {
                        this.saving = false;
                        nsSnackBar.success( result.message || __( 'Ordem salva.' ) ).subscribe();
                    },
                    error: ( error: any ) => {
                        this.saving = false;
                        nsSnackBar.error( error.message || __( 'Falha ao salvar a ordem das categorias.' ) ).subscribe();
                    },
                } );
        },
    },
};
</script>
