<p align="center"><h1 class="heading">PRODUCT CRUD API (Laravel)</h1></p>

## About This API

This is the product crud API, where we can create products with variations. Apis is available to manage variation options, values, and variation sets. So if you want to create the product through the API, you need to create an option set that will be selected during product creation. Please follow these steps to create variations and products:

- Create variation options by using this API: {{base_url}}/api/option/save [POST]
- Get variation options by using this API: {{base_url}}/api/options [GET]
- Delete variation options by using this API: {{base_url}}/api/option/delete [POST]

- Create option values by using this API: {{base_url}}/api/option/value/save [POST]
- Get option values by using this API: {{base_url}}/api/option_values [GET]
- Delete option value by using this API: {{base_url}}/api/option/value/delete [POST]

- Create option set by using this API: {{base_url}}/api/option/set/save [POST]
- Get option set data by using this API: {{base_url}}/api/option_set [GET]
- Delete option set by using this API: {{base_url}}/api/option/set/delete [POST]

After creating the option set, call the product creation API:

- Create product by using this API: {{base_url}}/api/product/save [POST]
- Get product list by using this API: {{base_url}}/api/products [GET]
- Delete product by using this API: {{base_url}}/api/product/delete [POST]


