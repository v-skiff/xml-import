<?php

namespace App\Http\Controllers;

use App\Category;
use App\Product;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    private $oldProductIds;
    private $oldCategoryIds;
    private $allowedMimeTypes;
    private $maxSize;
    private $report;

    public function __construct()
    {
        $this->maxSize = (int)ini_get('upload_max_filesize') * 1000;
        $this->allowedMimeTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        $this->report['count'] = 0;
        $this->report['failed_count'] = 0;
        $this->report['duplicates'] = [];
        $this->report['broken_data'] = [];
    }

    public function index() {
        $maxSizeMb = ini_get("upload_max_filesize");
        return view('import_xml/index', compact('maxSizeMb'));
    }

    public function importXML(Request $request)
    {
        // validation
        $request->validate([
            'xml_content' => [
                'bail',
                'required',
                'mimetypes:' . implode(',', $this->allowedMimeTypes),
                'mimes:xlsx,xls',
                'max:'.$this->maxSize
            ]
        ]);

        // store
        if ($request->hasFile('xml_content') &&
            $request->file('xml_content')->isValid()) {
            $fileStoragePath = $request->xml_content->storeAs('uploads', time() . '_content.xlsx');
        }

        if (! isset($fileStoragePath)) {
            return redirect()->back()
                ->withErrors('Something go wrong. File was not save.');
        }

        // parsing and import
        $this->oldCategoryIds = Category::where('id', '>', 0)->pluck( 'id', 'name')->toArray();
        $this->oldProductIds = Product::where('id', '>', 0)->pluck('id', 'code')->toArray();

        $filePath = storage_path('app/' . $fileStoragePath);

        if (Storage::exists($fileStoragePath)) {
            $start = time();

            $reader = ReaderEntityFactory::createReaderFromFile($filePath);

            $reader->open($filePath);

            $isFirstRow = true;
            $products = [];
            $newProducts = [];

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    if ($isFirstRow) {
                        $isFirstRow = false;
                        continue;
                    }

                    $cells = $row->getCells();
                    $structuredData = array_slice($cells, -7);
                    $categories_raw = array_slice($cells, 0, count($cells) - 7);

                    if (isset($oldProductIds[$structuredData[2]->getValue()])) {
                        continue;
                    }

                    $categoriesChainIds = $this->createCategoriesChain($categories_raw);

                    if ($this->validateProductData($structuredData)) {
                        $productCode = trim($structuredData[2]->getValue());
                        $products[] = [
                            'manufacturer' => $structuredData[0]->getValue(),
                            'name' => $structuredData[1]->getValue(),
                            'code' => $productCode,
                            'description' => $structuredData[3]->getValue(),
                            'price' => $structuredData[4]->getValue(),
                            'guarantee' => $structuredData[5]->getValue(),
                            'in_stock' => $structuredData[6]->getValue(),
                        ];

                        $newProducts[$productCode] = $categoriesChainIds;
                        $this->oldProductIds[$productCode] = true;
                        $this->report['count']++;
                    }

                }
            }

            $reader->close();

            foreach (array_chunk($products,1000) as $part) {
                Product::insert($part);
            }

            // define relationships
            $addedProductCodes = array_keys($newProducts);
            $addedProducts = Product::whereIn('code', $addedProductCodes)->pluck('id', 'code');
            $pivotQueryData = [];
            foreach($newProducts as $code => $categoryIds) {
                foreach($categoryIds as $categoryId) {
                    $pivotQueryData[] = [
                        'product_id' => $addedProducts[$code],
                        'category_id' => $categoryId,
                    ];
                }
            }

            DB::table('category_product')->insert($pivotQueryData);

            $this->report['execution_time'] = time() - $start;
        }

        return redirect()->back()
                ->with('report', $this->report);
    }

    public function validateProductData($data)
    {
        // $data[2] - product code
        $code = trim($data[2]->getValue());
        if (isset($this->oldProductIds[$code])) {
            $this->report['failed_count']++;
            $this->report['duplicates'][] = $code;
            return false;
        }
        foreach($data as $attr) {
            if (empty($attr->getValue())) {
                $this->report['failed_count']++;
                $this->report['broken_data'][] = $code;
                return false;
            }
        }
        return true;
    }

    public function createCategoriesChain($categories)
    {
        $categoriesChainIds = [];
        if (! empty($categories)) {
            $parentId = 0;
            foreach($categories as $category) {
                $name = $category->getValue();

                if (empty($name)) {
                    continue;
                }

                if (isset($this->oldCategoryIds[$name])) {
                    $parentId = $this->oldCategoryIds[$name];
                    $categoriesChainIds[] = $this->oldCategoryIds[$name];
                    continue;
                }

                $category_obj = Category::create([
                    'name' => $name,
                    'parent_id' => $parentId
                ]);

                $parentId = $category_obj->id;
                $this->oldCategoryIds[$name] = $category_obj->id;
                $categoriesChainIds[] = $category_obj->id;
            }
        }
        return $categoriesChainIds;
    }
}
