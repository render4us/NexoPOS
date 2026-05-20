<?php

use App\Classes\Schema;
use App\Models\ProductCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table( 'nexopos_products_categories', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products_categories', 'position' ) ) {
                $table->integer( 'position' )->default( 0 )->index();
            }
        } );

        /**
         * Backfill: para cada grupo de irmãs (mesmo parent_id, tratando 0 e null como raiz),
         * inicializamos position de forma incremental seguindo a ordem alfabética atual.
         */
        $groups = ProductCategory::orderBy( 'name' )->get()->groupBy( function ( $category ) {
            return (int) ( $category->parent_id ?? 0 );
        } );

        foreach ( $groups as $siblings ) {
            $index = 0;
            foreach ( $siblings as $category ) {
                $category->position = $index++;
                $category->save();
            }
        }
    }

    public function down(): void
    {
        Schema::table( 'nexopos_products_categories', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_products_categories', 'position' ) ) {
                $table->dropColumn( 'position' );
            }
        } );
    }
};
