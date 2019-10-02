<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Maatwebsite\Excel\Facades\Excel;
use App\Article;
use App\BudgetItem;
use App\Category;
use App\Unit;
use DB;

class ImportArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saes:ImportarArticulos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importacion de Articulos al sistema Saes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $this->info("Importando Articulos a SAES");
        $path = storage_path('excel/import/articulos.xlsx');
        $this->info($path);
        //Excel::selectSheets('sheet1')->load($path);
        Excel::selectSheetsByIndex(0)->load($path , function($reader) {
            $this->info("SE ENCONTRO EL ARCHIVO EXCEL");
            // reader methods
            $result = $reader->select(array('descripcion','unidad','categoria','numero','partida'))
            // ->take(100)
             ->get();
             $bar = $this->output->createProgressBar(count($result));
            foreach($result as $row){
                // $empleado = DB::table('rrhh.employees')->where('identity_card',$row->identity_card)->first();
                $partida = BudgetItem::where('number',$row->numero)->first();
                if(!$partida){
                    // $this->info('No se encontro: '.$row->numero);
                }
                $unidad = Unit::where('name','like','%'.trim($row->unidad).'%')->first();
                if(!$unidad){

                    $unidad = new Unit;
                    $unidad->name = trim($row->unidad);
                    $unidad->short_name = trim($row->unidad);
                    $unidad->save();

                    // $this->info('No se encontro la unidad y se la creo:'.$row->unidad );

                }    
                $categoria = Category::where('name','like','%'.trim($row->categoria).'%')->first();
                if(!$categoria)
                {
                    $categoria = new Category;
                    $categoria->name = trim($row->categoria);
                    $categoria->save();

                    // $this->info('No se encontrol categoria y se la creo '.$row->categoria);
                }   

                $new_article = new Article;
                $new_article->code = "";
                $new_article->name = $row->descripcion;
                $new_article->budget_item_id = $partida->id;
                $new_article->category_id = $categoria->id;
                $new_article->unit_id = $unidad->id;
                $new_article->save();
                $new_article->code = $partida->number."-".$new_article->id;
                $new_article->save();
                $bar->advance();
         
            }
            $bar->finish();
            $this->info('total rows:'.$result->count());
        });
    }
}
