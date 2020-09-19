Dengine - Datatable Helper for Laravel
=====

Built to have a better integration, data clean-up and optimisation of datatable server side data. This is an alternative server side processing implementation to match the [DataTables](https://datatables.net/manual/server-side) Library. Feel free to modify and improve any methods specific to your requirements.


Installation
------------

Install using composer:

```bash
composer require chikolokoy08/dengine
```

Laravel (optional)
------------------

Add the service provider in `config/app.php`:

```php
Chikolokoy08\Dengine\DengineServiceProvider::class
```

And add the Dengine alias to `config/app.php`:

```php
'Dengine' => Chikolokoy08\Dengine\Facades\Dengine::class
```

Basic Instance
-----------

Start by creating a `Dengine` instance (or use the `Dengine` Facade if you are using Laravel):

```php
use Chikolokoy08\Dengine\Dengine;

$dengine = new Dengine();
```

Usage On Eloquent
-----------

```php
public function getDatatableEvents(Request $request)
{
	try {
		$inputs = $request->all();
		$user_id = 1;

		$dengine = new Dengine();
		$dengine::parse($inputs);

		//MODEL INSTANCE
		$model = new \App\Models\Event();

		//TOTAL RECORDS QUERY
		$total = $model::where('user_id', $user_id)->where('target', 'like', '%'.$dengine::search_keyword().'%')->count();

		//QUERY WITH FULL PARAMETERS
		$query = $model::where('user_id', $user_id)->where('target', 'like', '%'.$dengine::search_keyword().'%')
			->skip($dengine::skip())
			->take($dengine::limit())
			->orderBy('column', $dengine::order_type())
			->get();

		//SETTER FOR TOTAL
		$dengine::recordsTotal($total);

		//PREPARING QUERY RESULT INTO DATATABLE JSON FORMAT
		$dengine::prepare($query->toArray());

		//RETURN AS JSON OBJECT
		return $dengine::make();	

	} catch (Exception $e) {
		\Log::error($e);
	}
}
```

Usage On Active Records
-----------

```php
public function getDatatableEvents(Request $request)
{
	try {
		$inputs = $request->all();
		$user_id = 1;

		$dengine = new Dengine();
		$dengine::parse($inputs);

		//MODEL INSTANCE
		$model = new \App\Models\Event();
		$dtparams = $dengine::setParameters(['user_id' => $user_id]);

		//TOTAL RECORDS QUERY
		$total = $model->eventQueryModel($dtparams);

		//Change $dtparams['get_total'] false
		$dtparams['get_total'] = false;

		//QUERY WITH FULL PARAMETERS
		$query = $model->eventQueryModel($dtparams);

		//SETTER FOR TOTAL
		$dengine::recordsTotal($total);

		//PREPARING QUERY RESULT INTO DATATABLE JSON FORMAT
		$dengine::prepare($query);

		//BONUS: YOU CAN FORMAT RETURNED DATA ACCORDING TO YOUR REQUIREMENTS BEFORE RETURNING AS JSON OBJECT
		$dengine::add_column('formatted_date', function($row) {
		    return date('d/m/Y', strtotime($row->date));
		});
		$dengine::add_column('user_display', function($row) use($user_id) {
		    return "This is user #{$user_id}";
		});

		//RETURN AS JSON OBJECT
		return $dengine::make();	

	} catch (Exception $e) {
		\Log::error($e);
	}
}
```

Suggested Model Structure
-----------

```php
public function eventQueryModel($qp=[])
{
	try {
		$column_orderable 	= [ 'title', 'target', 'event_date'];
		$qp['column_text']	= ($qp['column'] == '' ? $column_orderable[0] : $column_orderable[$qp['column']);
		$take           		= $get_total == true || $keyword != '' ? 5000000 : $take;
		$whereClauses     	= "t.user_id = {$qp['user_id']}";
		
		//IF KEYWORD IS NOT EMPTY
		if (!empty($qp['keyword'])) {
			$whereClauses .= " AND ( t.title LIKE '%$keyword%' OR t.target LIKE '%$keyword%' OR t.date LIKE '%$keyword%')";
		}

		$query = \DB::select("
		    SELECT
		        t.id,
		        t.user_id,
		        t.title,
		        t.target,
		        DATE_FORMAT(t.date, '%d %M %Y') as event_date
		    FROM
		        {$this->table} as t
		    WHERE
		        {$whereClauses}
		    GROUP BY t.id
		    ORDER BY {$qp['column_text']} {$qp['order_type']}
		    LIMIT {$qp['skip']}, {$qp['take']}
		");

		return $query;		

	} catch (Exception $e) {
		\Log::error($e);
	}
}
```

Suggested Full Controller
-----------

```php

namespace App\Http\Controllers;

use Chikolokoy08\Dengine\Dengine;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DatatableController extends Controller
{
    protected $dengine;

    public function __construct(Request $request)
    {
    	try {
	    	$this->dengine = new Dengine();
	    	$this->dengine::parse($request->all());    		
    	} catch (Exception $e) {
    		\Log::error($e);
    	}
    }

		public function getDatatableEvents(Request $request)
		{
			try {
				//MODEL INSTANCE
				$model = new \App\Models\Event();
				$dtparams = $this->dengine::setParameters(['user_id' => $user_id]);

				//TOTAL RECORDS QUERY
				$total = $model->eventQueryModel($dtparams);

				//Change $dtparams['get_total'] false
				$dtparams['get_total'] = false;

				//QUERY WITH FULL PARAMETERS
				$query = $model->eventQueryModel($dtparams);

				//SETTER FOR TOTAL
				$this->dengine::recordsTotal($total);

				//PREPARING QUERY RESULT INTO DATATABLE JSON FORMAT
				$this->dengine::prepare($query);

				//BONUS: YOU CAN FORMAT FETCHED DATA ACCORDING TO YOUR REQUIREMENTS BEFORE RETURNING AS JSON OBJECT
				$this->dengine::add_column('formatted_date', function($row) {
				    return date('d/m/Y', strtotime($row->date));
				});
				$this->dengine::add_column('user_display', function($row) use($user_id) {
				    return "This is user #{$user_id}";
				});
				$this->dengine::add_column('actions', function($row) use($user_id) {
				    return "<a class='edit-btn' href='#' data-id='{$user_id}'>Edit User</a>";
				});

				//RETURN AS JSON OBJECT
				return $this->dengine::make();

			} catch (Exception $e) {
				\Log::error($e);
			}
		}
}

```

Returned JSON Format
-----------

```json
	{
		"draw":"1",
		"recordsTotal":0,
		"recordsFiltered":0,
		"server_cols":"",
		"data":[]
	}
```

*Note, the version method is still in beta, so it might not return the correct result.*

## License

Datatable Engine Laravel is licensed under [The MIT License (MIT)](LICENSE).

##Support

If you have questions just send an email at [cmabugay@gmail.com](mailto:cmabugay@gmail.com)
