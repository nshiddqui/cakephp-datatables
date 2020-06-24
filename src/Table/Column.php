<?php
/**
 * Copyright (c) Allan Carvalho 2020.
 * Under Mit License
 *
 * link:     https://github.com/wsssoftware/cakephp-data-renderer
 * author:   Allan Carvalho <allan.m.carvalho@outlook.com>
 * license:  MIT License https://github.com/wsssoftware/cakephp-datatables/blob/master/LICENSE
 */
declare(strict_types = 1);

namespace DataTables\Table;

use Cake\Database\Expression\FunctionExpression;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use DataTables\Tools\Validator;
use InvalidArgumentException;

/**
 * Class Column
 *
 * Created by allancarvalho in abril 17, 2020
 */
final class Column {

	const TYPE_DATE = 'date';
	const TYPE_NUM = 'num';
	const TYPE_NUM_FMT = 'num-fmt';
	const TYPE_HTML_NUM = 'html-num';
	const TYPE_HTML_NUM_FMT = 'html-num-fmt';
	const TYPE_HTML = 'html';
	const TYPE_STRING = 'string';
	const VALID_TYPES = [
		self::TYPE_DATE,
		self::TYPE_NUM,
		self::TYPE_NUM_FMT,
		self::TYPE_HTML_NUM,
		self::TYPE_HTML_NUM_FMT,
		self::TYPE_HTML,
		self::TYPE_STRING,
	];
	const DOM_TEXT = 'dom-text';
	const DOM_SELECT = 'dom-select';
	const DOM_CHECKBOX = 'dom-checkbox';
	const VALID_ORDER_DATA_TYPES = [
		self::DOM_TEXT,
		self::DOM_SELECT,
		self::DOM_CHECKBOX,
	];
	const DATA_TABLES_TYPE_MAP = [
		'tinyinteger' => 'num',
		'smallinteger' => 'num',
		'integer' => 'num',
		'biginteger' => 'num',
		'binary' => 'string',
		'binaryuuid' => 'string',
		'boolean' => 'num',
		'date' => 'date',
		'datetime' => 'date',
		'datetimefractional' => 'date',
		'decimal' => 'num',
		'float' => 'num',
		'json' => 'string',
		'string' => 'string',
		'char' => 'string',
		'text' => 'string',
		'time' => 'date',
		'timestamp' => 'date',
		'timestampfractional' => 'date',
		'timestamptimezone' => 'date',
		'uuid' => 'string',
	];

	/**
	 * @var array
	 */
	private $_config = [
		'cellType' => null,
		'className' => null,
		'contentPadding' => null,
		'createdCell' => null,
		'name' => null,
		'orderData' => null,
		'orderDataType' => null,
		'orderSequence' => null,
		'orderable' => null,
		'searchable' => null,
		'title' => null,
		'type' => null,
		'visible' => null,
		'width' => null,
	];

	/**
	 * If the column is or not a database column.
	 *
	 * @var array
	 */
	private $_columnSchema;

	/**
	 * @var bool
	 */
	private $_database;

	/**
	 * @var string
	 */
	private $_associationPath = '';

	/**
	 * @var \Cake\Database\Expression\FunctionExpression|null
	 */
	private $_functionExpression = null;

	/**
	 * Column constructor.
	 *
	 * @param string $name
	 * @param bool $database
	 * @param array $columnSchema
	 * @param string $associationPath
	 * @param \Cake\Database\Expression\FunctionExpression|null $functionExpression
	 */
	public function __construct(string $name, bool $database = true, array $columnSchema = [], string $associationPath = '', FunctionExpression $functionExpression = null) {
		$title = explode('.', $name);
		if (count($title) === 2) {
			$title = $title[1];
		} else {
			$title = $title[0];
		}
	    $title = Inflector::humanize($title);
		$this->_config = Hash::insert($this->_config, 'name', $name);
		$this->_config = Hash::insert($this->_config, 'title', $title);
		$this->_database = $database;
		$this->_columnSchema = $columnSchema;
		$this->_associationPath = $associationPath;
		$this->_functionExpression = $functionExpression;
		if ($database === true && !empty($columnSchema['type']) && !empty(static::DATA_TABLES_TYPE_MAP[$columnSchema['type']])) {
			$this->setType(static::DATA_TABLES_TYPE_MAP[$columnSchema['type']]);
		}
	}

	/**
	 * @return string
	 */
	public function getAssociationPath(): string {
		return $this->_associationPath;
	}

	/**
	 * @return \Cake\Database\Expression\FunctionExpression
	 */
	public function getFunctionExpression(): ?FunctionExpression {
		return $this->_functionExpression;
	}

	/**
	 * @param bool $onlyDirty
	 * @param bool $isDefaultColumn
	 * @return array
	 */
	public function getConfig(bool $onlyDirty = true, bool $isDefaultColumn = false): array {
		$result = [];
		if ($onlyDirty === false) {
			$result = $this->_config;
		}
		if (empty($result)) {
			foreach ($this->_config as $index => $item) {
				if ($item !== null) {
					$result[$index] = $item;
				}
			}
		}
		if ($isDefaultColumn === true) {
			$result['targets'] = '_all';
			unset($result['name']);
			unset($result['title']);
		}
		return $result;
	}

	/**
	 * Get column name
	 *
	 * @return string
	 */
	public function getName(): string {
		return Hash::get($this->_config, 'name');
	}

	/**
	 * Check if is a database column or not.
	 *
	 * @return bool
	 */
	public function isDatabase(): bool {
		return $this->_database;
	}

	/**
	 * @param string|null $name
	 * @return mixed
	 */
	public function getColumnSchema(string $name = null) {
		if (empty($name)) {
			return $this->_columnSchema;
		}
		return Hash::get($this->_columnSchema, $name);
	}

	/**
	 * Getter method.
	 * Change the cell type created for the column - either TD cells or TH cells.
	 * This can be useful as TH cells have semantic meaning in the table body, allowing them to act as a header for a
	 * row (you may wish to add scope='row' to the TH elements using columns.createdCell option).
	 *
	 * @return string|null
	 * @link   https://datatables.net/reference/option/columns.cellType
	 */
	public function getCellType(): ?string {
		return Hash::get($this->_config, 'cellType');
	}

	/**
	 * Setter method.
	 * Change the cell type created for the column - either TD cells or TH cells.
	 * This can be useful as TH cells have semantic meaning in the table body, allowing them to act as a header for a
	 * row (you may wish to add scope='row' to the TH elements using columns.createdCell option).
	 *
	 * @param string|null $cellType
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.cellType
	 */
	public function setCellType(?string $cellType): self {
		if (!in_array($cellType, ['td', 'th']) && !empty($cellType)) {
			throw new InvalidArgumentException("\$cellType must be 'td' or 'th'. Found: $cellType.");
		}
		$this->_config = Hash::insert($this->_config, 'cellType', $cellType);
		return $this;
	}

	/**
	 * Getter method.
	 * Quite simply this option adds a class to each cell in a column, regardless of if the table source is from DOM,
	 * Javascript or Ajax. This can be useful for styling columns.
	 *
	 * @return string|null
	 * @link   https://datatables.net/reference/option/columns.className
	 */
	public function getClassName(): ?string {
		return Hash::get($this->_config, 'className');
	}

	/**
	 * Setter method.
	 * Quite simply this option adds a class to each cell in a column, regardless of if the table source is from DOM,
	 * Javascript or Ajax. This can be useful for styling columns.
	 *
	 * @param string|null $className
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.className
	 */
	public function setClassName(?string $className): self {
		$this->_config = Hash::insert($this->_config, 'className', $className);
		return $this;
	}

	/**
	 * Getter method.
	 * Quite simply this option adds a class to each cell in a column, regardless of if the table source is from DOM,
	 * Javascript or Ajax. This can be useful for styling columns.
	 *
	 * @return string|null
	 * @link   https://datatables.net/reference/option/columns.contentPadding
	 */
	public function getContentPadding(): ?string {
		return Hash::get($this->_config, 'contentPadding');
	}

	/**
	 * Setter method.
	 * The first thing to say about this property is that generally you shouldn't need this!
	 *
	 * Having said that, it can be useful on rare occasions. When DataTables calculates the column widths to assign to
	 * each column, it finds the longest string in each column and then constructs a temporary table and reads the
	 * widths from that. The problem with this is that "mmm" is much wider then "iiii", but the latter is a longer
	 * string - thus the calculation can go wrong (doing it properly and putting it into an DOM object and measuring
	 * that is horribly slow!). Thus as a "work around" we provide this option. It will append its value to the text
	 * that is found to be the longest string for the column - i.e. padding.
	 *
	 * @param string $contentPadding
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.contentPadding
	 */
	public function setContentPadding(?string $contentPadding): self {
		$this->_config = Hash::insert($this->_config, 'contentPadding', $contentPadding);
		return $this;
	}

	/**
	 * Setter method.
	 * This is a callback function that is executed whenever a cell is created (Ajax source, etc) or read from a DOM
	 * source. It can be used as a complement to columns.render allowing modification of the cell's DOM element (add
	 * background colour for example) when the element is created (cells may not be immediately created on table
	 * initialisation if deferRender is enabled, or if rows are dynamically added using the API (rows.add()).
	 *
	 * This is the counterpart callback for rows, which use the createdRow option.
	 *
	 * Accessible parameters inside js function:
	 *  - cell (node) - The TD node that has been created.
	 *  - cellData (any) - Cell data. If you use columns.render to modify the data, use $(cell).html() to get and modify
	 *    the rendered data. The information given here is the original and unmodified data from the data source.
	 *  - rowData (any) - Data source object / array for the whole row.
	 *  - rowIndex (integer) - DataTables' internal index for the row.
	 *  - colIndex (integer) - DataTables' internal index for the column.
	 *
	 * @param array|string $bodyOrParams To use application template file, leave blank or pass an array with params
	 *                                   that will be used in file. To use the body mode, pass an js code that will
	 *                                   putted inside the js function.
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.createdCell
	 * @link   https://datatables.net/reference/type/node
	 * @link   https://datatables.net/reference/type/integer
	 */
	public function callbackCreatedCell($bodyOrParams = []): self {
		Validator::getInstance()->validateBodyOrParams($bodyOrParams);
		$this->_config = Hash::insert($this->_config, 'createdCell', $bodyOrParams);
		return $this;
	}

	/**
	 * Getter method.
	 * Allows a column's sorting to take either the data from a different (often hidden) column as the data to sort, or
	 * data from multiple columns.
	 *
	 * A common example of this is a table which contains first and last name columns next to each other, it is
	 * intuitive that they would be linked together to multi-column sort. Another example, with a single column, is the
	 * case where the data shown to the end user is not directly sortable itself (a column with images in it), but
	 * there is some meta data than can be sorted (e.g. file name) - note that orthogonal data is an alternative method
	 * that can be used for this.
	 *
	 * @return int|array|null
	 * @link   https://datatables.net/reference/option/columns.orderData
	 */
	public function getOrderData() {
		return Hash::get($this->_config, 'orderData');
	}

	/**
	 * Setter method.
	 * Allows a column's sorting to take either the data from a different (often hidden) column as the data to sort, or
	 * data from multiple columns.
	 *
	 * A common example of this is a table which contains first and last name columns next to each other, it is
	 * intuitive that they would be linked together to multi-column sort. Another example, with a single column, is the
	 * case where the data shown to the end user is not directly sortable itself (a column with images in it), but
	 * there is some meta data than can be sorted (e.g. file name) - note that orthogonal data is an alternative method
	 * that can be used for this.
	 *
	 * @param int|array|null $orderData
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.orderData
	 */
	public function setOrderData($orderData): self {
		$orderDataType = gettype($orderData);
		$validTypes = [
			'integer',
			'array',
			'NULL',
		];
		$validTypesString = str_replace(' and ', ' or ', Text::toList($validTypes));
		if (is_array($orderData)) {
			Validator::getInstance()->checkKeysValueTypesOrFail($orderData, 'integer', 'integer', '$orderData');
		} elseif ($orderDataType === 'integer' && $orderData < 0) {
			throw new InvalidArgumentException("In \$orderData must be greater or equal 0. Found: '$orderData'.");
		} elseif (!in_array($orderDataType, $validTypes)) {
			throw new InvalidArgumentException("In \$orderData you can use only $validTypesString. Found: '$orderDataType'.");
		}
		$this->_config = Hash::insert($this->_config, 'orderData', $orderData);
		return $this;
	}

	/**
	 * Getter method.
	 * DataTables' primary order method (the ordering feature) makes use of data that has been cached in memory rather
	 * than reading the data directly from the DOM every time an order is performed for performance reasons (reading
	 * from the DOM is inherently slow). However, there are times when you do actually want to read directly from the
	 * DOM, acknowledging that there will be a performance hit, for example when you have form elements in the table
	 * and the end user can alter the values. This configuration option is provided to allow plug-ins to provide this
	 * capability in DataTables.
	 *
	 * Please note that there are no columns.orderDataType plug-ins built into DataTables, they must be added
	 * separately. See the DataTables sorting plug-ins page for further information.
	 *
	 * @return string|null
	 * @link   https://datatables.net/reference/option/columns.orderDataType
	 * @link   https://datatables.net/plug-ins/sorting/
	 */
	public function getOrderDataType(): ?string {
		return Hash::get($this->_config, 'orderDataType');
	}

	/**
	 * Setter method.
	 * DataTables' primary order method (the ordering feature) makes use of data that has been cached in memory rather
	 * than reading the data directly from the DOM every time an order is performed for performance reasons (reading
	 * from the DOM is inherently slow). However, there are times when you do actually want to read directly from the
	 * DOM, acknowledging that there will be a performance hit, for example when you have form elements in the table
	 * and the end user can alter the values. This configuration option is provided to allow plug-ins to provide this
	 * capability in DataTables.
	 *
	 * Please note that there are no columns.orderDataType plug-ins built into DataTables, they must be added
	 * separately. See the DataTables sorting plug-ins page for further information.
	 *
	 * @param string|null $orderDataType
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.orderDataType
	 * @link   https://datatables.net/plug-ins/sorting/
	 */
	public function setOrderDataType(?string $orderDataType): self {
		Validator::getInstance()->inArrayOrFail($orderDataType, static::VALID_ORDER_DATA_TYPES);
		$this->_config = Hash::insert($this->_config, 'orderDataType', $orderDataType);
		return $this;
	}

	/**
	 * Getter method.
	 * You can control the default ordering direction, and even alter the behaviour of the order handler (i.e. only
	 * allow ascending sorting etc) using this parameter.
	 *
	 * @return array
	 * @link   https://datatables.net/reference/option/columns.orderSequence
	 */
	public function getOrderSequence(): ?array {
		return Hash::get($this->_config, 'orderSequence');
	}

	/**
	 * Setter method.
	 * You can control the default ordering direction, and even alter the behaviour of the order handler (i.e. only
	 * allow ascending sorting etc) using this parameter.
	 *
	 * @param array $orderSequence
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.orderSequence
	 */
	public function setOrderSequence(array $orderSequence = []): self {
		Validator::getInstance()->checkKeysValueTypesOrFail($orderSequence, 'integer', 'string', '$orderSequence');
		foreach ($orderSequence as $item) {
			if (!in_array($item, ['asc', 'desc'])) {
				throw new InvalidArgumentException("In \$orderDataType you can use only 'asc' or 'desc'. Found: '$item'.");
			}
		}
		$this->_config = Hash::insert($this->_config, 'orderSequence', $orderSequence);
		return $this;
	}

	/**
	 * Checker method.
	 * Using this parameter, you can remove the end user's ability to order upon a column. This might be useful for
	 * generated content columns, for example if you have 'Edit' or 'Delete' buttons in the table.
	 *
	 * Note that this option only affects the end user's ability to order a column. Developers are still able to order
	 * a column using the order option or the order() method if required.
	 *
	 * @return bool|null
	 * @link   https://datatables.net/reference/option/columns.orderable
	 */
	public function isOrderable(): ?bool {
		return Hash::get($this->_config, 'orderable');
	}

	/**
	 * Setter method.
	 * Using this parameter, you can remove the end user's ability to order upon a column. This might be useful for
	 * generated content columns, for example if you have 'Edit' or 'Delete' buttons in the table.
	 *
	 * Note that this option only affects the end user's ability to order a column. Developers are still able to order
	 * a column using the order option or the order() method if required.
	 *
	 * @param bool|null $orderable
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.orderable
	 */
	public function setOrderable(?bool $orderable): self {
		$this->_config = Hash::insert($this->_config, 'orderable', $orderable);
		return $this;
	}

	/**
	 * Checker method.
	 * Using this parameter, you can define if DataTables should include this column in the filterable data in the
	 * table. You may want to use this option to disable search on generated columns such as 'Edit' and 'Delete'
	 * buttons for example.
	 *
	 * @return bool|null
	 * @link   https://datatables.net/reference/option/columns.searchable
	 */
	public function isSearchable(): ?bool {
		return Hash::get($this->_config, 'searchable');
	}

	/**
	 * Setter method.
	 * Using this parameter, you can define if DataTables should include this column in the filterable data in the
	 * table. You may want to use this option to disable search on generated columns such as 'Edit' and 'Delete'
	 * buttons for example.
	 *
	 * @param bool|null $searchable
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.searchable
	 */
	public function setSearchable(?bool $searchable): self {
		$this->_config = Hash::insert($this->_config, 'searchable', $searchable);
		return $this;
	}

	/**
	 * Getter method.
	 * The titles of columns are typically read directly from the DOM (from the cells in the THEAD element), but it can
	 * often be useful to either override existing values, or have DataTables actually construct a header with column
	 * titles for you (for example if there is not a THEAD element in the table before DataTables is constructed). This
	 * option is available to provide that ability.
	 *
	 * Please note that when constructing a header, DataTables can only construct a simple header with a single cell
	 * for each column. Complex headers with colspan and rowspan attributes must either already be defined in the
	 * document, or be constructed using standard DOM / jQuery methods.
	 *
	 * @return string
	 * @link   https://datatables.net/reference/option/columns.title
	 */
	public function getTitle(): string {
		return Hash::get($this->_config, 'title');
	}

	/**
	 * Setter method.
	 * The titles of columns are typically read directly from the DOM (from the cells in the THEAD element), but it can
	 * often be useful to either override existing values, or have DataTables actually construct a header with column
	 * titles for you (for example if there is not a THEAD element in the table before DataTables is constructed). This
	 * option is available to provide that ability.
	 *
	 * Please note that when constructing a header, DataTables can only construct a simple header with a single cell
	 * for each column. Complex headers with colspan and rowspan attributes must either already be defined in the
	 * document, or be constructed using standard DOM / jQuery methods.
	 *
	 * @param string $title
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.title
	 */
	public function setTitle(string $title): self {
		$this->_config = Hash::insert($this->_config, 'title', $title);
		return $this;
	}

	/**
	 * Getter method.
	 * When operating in client-side processing mode, DataTables can process the data used for the display in each cell
	 * in a manner suitable for the action being performed. For example, HTML tags will be removed from the strings
	 * used for filter matching, while sort formatting may remove currency symbols to allow currency values to be
	 * sorted numerically. The formatting action performed to normalise the data so it can be ordered and searched
	 * depends upon the column's type.
	 *
	 * DataTables has a number of built in types which are automatically detected:
	 *  - date - Date / time values. Note that DataTables' built in date parsing uses Javascript's Date.parse() method
	 *    which supports only a very limited subset of dates. Additional date format support can be added through the
	 *    use of plug-ins.
	 *     - Sorting - sorted chronologically
	 *     - Filtering - no effect
	 *  - num - Simple number sorting.
	 *     - Sorting - sorted numerically
	 *     - Filtering - no effect
	 *  - num-fmt - Numeric sorting of formatted numbers. Numbers which are formatted with thousands separators,
	 *    currency symbols or a percentage indicator will be sorted numerically automatically by DataTables.
	 *     - Supported built-in currency symbols are $, £, € and ¥.
	 *     - Supported built-in thousands separators are ' and ,.
	 *    Examples:
	 *     - $100,000 - sorted as 100000
	 *     - £10'000 - sorted as 10000
	 *     - 5'000 - sorted as 5000
	 *     - 40% - sorted as 40
	 *     - Sorting - sorted numerically
	 *     - Filtering - no effect
	 *  - html-num - As per the num option, but with HTML tags also in the data.
	 *     - Sorting - sorted numerically
	 *     - Filtering - HTML tags removed from filtering string
	 *  - html-num-fmt - As per the num-fmt option, but with HTML tags also in the data.
	 *     - Sorting - sorted numerically
	 *     - Filtering - HTML tags removed from filtering string
	 *  - html - Basic string processing for HTML tags
	 *     - Sorting - sorted with HTML tags removed
	 *     - Filtering - HTML tags removed from filtering string
	 *  - string - Fall back type if the data in the column does not match the requirements for the other data types
	 *    (above).
	 *     - Sorting - no effect
	 *     - Filtering - no effect
	 *
	 * It is expected that the above options will cover the majority of data types used with DataTables, however, data
	 * is flexible and comes in many forms, so additional types with different effects can be added through the use of
	 * plug-ins. This provides the ability to sort almost any data format imaginable!
	 *
	 * As an optimisation, if you know the column type in advance, you can set the value using this option, saving
	 * DataTables from running its auto detection routine.
	 *
	 * Please note that if you are using server-side processing (serverSide) this option has no effect since the
	 * ordering and search actions are performed by a server-side script.
	 *
	 * @return string|null
	 * @link   https://datatables.net/reference/option/columns.type
	 */
	public function getType(): ?string {
		return Hash::get($this->_config, 'type');
	}

	/**
	 * Setter method.
	 * When operating in client-side processing mode, DataTables can process the data used for the display in each cell
	 * in a manner suitable for the action being performed. For example, HTML tags will be removed from the strings
	 * used for filter matching, while sort formatting may remove currency symbols to allow currency values to be
	 * sorted numerically. The formatting action performed to normalise the data so it can be ordered and searched
	 * depends upon the column's type.
	 *
	 * DataTables has a number of built in types which are automatically detected:
	 *  - date - Date / time values. Note that DataTables' built in date parsing uses Javascript's Date.parse() method
	 *    which supports only a very limited subset of dates. Additional date format support can be added through the
	 *    use of plug-ins.
	 *     - Sorting - sorted chronologically
	 *     - Filtering - no effect
	 *  - num - Simple number sorting.
	 *     - Sorting - sorted numerically
	 *     - Filtering - no effect
	 *  - num-fmt - Numeric sorting of formatted numbers. Numbers which are formatted with thousands separators,
	 *    currency symbols or a percentage indicator will be sorted numerically automatically by DataTables.
	 *     - Supported built-in currency symbols are $, £, € and ¥.
	 *     - Supported built-in thousands separators are ' and ,.
	 *    Examples:
	 *     - $100,000 - sorted as 100000
	 *     - £10'000 - sorted as 10000
	 *     - 5'000 - sorted as 5000
	 *     - 40% - sorted as 40
	 *     - Sorting - sorted numerically
	 *     - Filtering - no effect
	 *  - html-num - As per the num option, but with HTML tags also in the data.
	 *     - Sorting - sorted numerically
	 *     - Filtering - HTML tags removed from filtering string
	 *  - html-num-fmt - As per the num-fmt option, but with HTML tags also in the data.
	 *     - Sorting - sorted numerically
	 *     - Filtering - HTML tags removed from filtering string
	 *  - html - Basic string processing for HTML tags
	 *     - Sorting - sorted with HTML tags removed
	 *     - Filtering - HTML tags removed from filtering string
	 *  - string - Fall back type if the data in the column does not match the requirements for the other data types
	 *    (above).
	 *     - Sorting - no effect
	 *     - Filtering - no effect
	 *
	 * It is expected that the above options will cover the majority of data types used with DataTables, however, data
	 * is flexible and comes in many forms, so additional types with different effects can be added through the use of
	 * plug-ins. This provides the ability to sort almost any data format imaginable!
	 *
	 * As an optimisation, if you know the column type in advance, you can set the value using this option, saving
	 * DataTables from running its auto detection routine.
	 *
	 * Please note that if you are using server-side processing (serverSide) this option has no effect since the
	 * ordering and search actions are performed by a server-side script.
	 *
	 * @param string|null $type
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.type
	 */
	public function setType(?string $type): self {
		Validator::getInstance()->inArrayOrFail($type, static::VALID_TYPES);
		$this->_config = Hash::insert($this->_config, 'type', $type);
		return $this;

	}

	/**
	 * Checker method.
	 * DataTables and show and hide columns dynamically through use of this option and the column().visible() /
	 * columns().visible() methods. This option can be used to get the initial visibility state of the column, with the
	 * API methods used to alter that state at a later time.
	 *
	 * This can be particularly useful if your table holds a large number of columns and you wish the user to have the
	 * ability to control which columns they can see, or you have data in the table that the end user shouldn't see
	 * (for example a database ID column).
	 *
	 * @return bool|null
	 * @link   https://datatables.net/reference/option/columns.visible
	 */
	public function isVisible(): ?bool {
		return Hash::get($this->_config, 'visible');
	}

	/**
	 * Setter method.
	 * DataTables and show and hide columns dynamically through use of this option and the column().visible() /
	 * columns().visible() methods. This option can be used to get the initial visibility state of the column, with the
	 * API methods used to alter that state at a later time.
	 *
	 * This can be particularly useful if your table holds a large number of columns and you wish the user to have the
	 * ability to control which columns they can see, or you have data in the table that the end user shouldn't see
	 * (for example a database ID column).
	 *
	 * @param bool|null $visible
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.visible
	 */
	public function setVisible(?bool $visible): self {
		$this->_config = Hash::insert($this->_config, 'visible', $visible);
		return $this;
	}

	/**
	 * Getter method.
	 * This parameter can be used to define the width of a column, and may take any CSS value (3em, 20px etc).
	 *
	 * Please note that pixel perfect column width is virtually impossible to achieve in tables with dynamic content,
	 * so do not be surprised if the width of the column if off by a few pixels from what you assign using this
	 * property. Column width in tables depends upon many properties such as cell borders, table borders, the
	 * border-collapse property, the content of the table and many other properties. Both DataTables and the browsers
	 * attempt to lay the table out in an optimal manner taking this options all into account.
	 *
	 * @return string|null
	 * @link   https://datatables.net/reference/option/columns.width
	 */
	public function getWidth(): ?string {
		return Hash::get($this->_config, 'width');
	}

	/**
	 * Setter method.
	 * This parameter can be used to define the width of a column, and may take any CSS value (3em, 20px etc).
	 *
	 * Please note that pixel perfect column width is virtually impossible to achieve in tables with dynamic content,
	 * so do not be surprised if the width of the column if off by a few pixels from what you assign using this
	 * property. Column width in tables depends upon many properties such as cell borders, table borders, the
	 * border-collapse property, the content of the table and many other properties. Both DataTables and the browsers
	 * attempt to lay the table out in an optimal manner taking this options all into account.
	 *
	 * @param string|null $width
	 *
	 * @return \DataTables\Table\Column
	 * @link   https://datatables.net/reference/option/columns.width
	 */
	public function setWidth(?string $width): self {
		$this->_config = Hash::insert($this->_config, 'width', $width);

		return $this;

	}

}
