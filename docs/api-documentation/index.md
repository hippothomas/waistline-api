# API Documentation

## Getting Started
### API Authentication
For every API request you make, you will need to make sure to be authenticated with the API by passing your API key to the request header. You can find an example below.

**Example API Request:**
```bash hl_lines="2"
curl '<APP_BASE_URL>/api/v1/stats' \
	-H "Authorization: ApiKey <YOUR_API_KEY>"
```

??? note "Where to find your API Key?"

    Your API Key is located in the "Service Authorization Header" section, that you can find in your [Dashboard](../dashboard.md). It's the text on the right of `ApiKey`. It looks like this: `018d2452-6b7f-7574-a758-68375a1fbe40`.

!!! warning "Important"

    Please make sure not to expose your API key publicly. If you believe your API key may be compromised, you should always regenerate it.

### 256-bit HTTPS Encryption
You should always use HTTPS to access the API.
Accessing an API endpoint using HTTPS is crucial for ensuring secure and encrypted communication, protecting sensitive data from unauthorized access and potential security threats.

### API Errors
API errors consist of status code and message response. If an error occurs, the API will also return HTTP status codes, such as 404 for "not found" errors. If your API request succeeds, HTTP status code 200 will be sent.

**Example Error:**
```json
{
    "status": 401,
    "message": "Your API Key is not valid."
}
```

## API Features

### Statistics Data <label class="http get"></label>
You can use the API's `stats` endpoint in order to obtain nutrition data from one or multiple days of your diary.

**Example API Request:**<br/>
```bash
curl '<APP_BASE_URL>/api/v1/stats' \
	-H "Authorization: ApiKey <YOUR_API_KEY>"
```

**HTTP GET Request Parameters:**<br/>

| Object      | Description                                                                                                                                                                                                                             |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `date_from` | Filter results based on a specific timeframe by passing a from-date in `YYYY-MM-DD` format. You can also specify an exact time in ISO-8601 date format, e.g. `2024-01-01T00:00:00.000Z`.                                                |
| `date_to`   | Filter results based on a specific timeframe by passing an end-date in `YYYY-MM-DD` format. You can also specify an exact time in ISO-8601 date format, e.g. `2024-01-01T00:00:00.000Z`.                                                |
| `interval`  | Filter results based on an interval in the timeframe selected. Available values: `day` (Default), `week`, `month`, `year`.                                                                                                              |
| `fields`    | Select information needed and optimize bandwidth with a partial response. Example: If you want to only need the values for `calories` and `proteins`, you should set the value `calories,proteins`. By default, everything is returned. |
| `sort`      | By default, results are sorted by date/time descending. Use this parameter to specify a sorting order. Available values: `DESC` (Default), `ASC`.                                                                                       |
| `limit`     | Specify a pagination limit (number of results per page) for your API request. Default limit value is `100`, maximum allowed limit value is `500`.                                                                                       |
| `offset`    | Specify a pagination offset value for your API request. Example: An offset value of `100` combined with a limit value of 10 would show results 100-110. Default value is `0`, starting with the first available result.                 |

**Example API Response:**<br/>
```json
{
    "pagination": {
        "limit": 100,
        "offset": 0,
        "total": 6,
        "count": 6
    },
    "results": {
        "2024-01-01": {
            "calories": 2167.44,
            "kilojoules": 9102.75,
            "fat": 73.96,
            "carbohydrates": 196.78,
            "sugars": 51.87,
            "fiber": 18.36,
            "proteins": 172.58,
            "saturated-fat": 34.11,
            "salt": 2.77
        },
        [...]
    }
}
```

**API Response Objects:**<br/>

| Response Object          | Description                                            |
|--------------------------|--------------------------------------------------------|
| `pagination` > `limit`   | Returns your pagination limit value.                   |
| `pagination` > `offset`  | Returns your pagination offset value.                  |
| `pagination` > `count`   | Returns the results count on the current page.         |
| `pagination` > `total`   | Returns the total count of results available.          |
| `results`                | Returns the list of all the results.                   |
| `results` > `YYYY-MM-DD` | Returns the nutrition data of the current data object. |

### Journal Data <label class="http get"></label>
Using the API's `journal` endpoint you will be able to look up all information about a specific day of your diary.

**Example API Request:**<br/>
```bash
curl '<APP_BASE_URL>/api/v1/journal' \
	-H "Authorization: ApiKey <YOUR_API_KEY>"
```

**HTTP GET Request Parameters:**<br/>

| Object | Description                                                                                                                                                                  |
|--------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `date` | Select result based on a specific day by passing a date in `YYYY-MM-DD` format. You can also specify an exact time in ISO-8601 date format, e.g. `2024-01-01T00:00:00.000Z`. |

**Example API Response:**<br/>
```json
{
    "pagination": {
        "count": 1
    },
    "results": [
        {
            "nutrition": {
                [...]
            },
            "entryDetails": [
                [...]
            ],
            "entry": {
                "dateTime": "2024-01-01T00:00:00.000Z",
                "items": [
                    [...]
                ],
                "stats": [],
                "id": 44
            }
        }
    ]
}
```

**API Response Objects:**<br/>

| Response Object            | Description                                                            |
|----------------------------|------------------------------------------------------------------------|
| `pagination` > `count`     | Returns the results count on the current page.                         |
| `results`                  | Returns the list of all the results.                                   |
| `results` > `nutrition`    | Returns the nutrition data of the current day.                         |
| `results` > `entryDetails` | Returns the details about each specific food entry of the current day. |
| `results` > `entry`        | Returns the general entry info with an items list of the current day.  |

!!! note

    The data returned could change a lot from one person to another depending on how you configure your mobile app and on what data you send, but this template should be the same. 

### Synchronisation Endpoint <label class="http post"></label>
The `sync` endpoint is used to synchronise the Waistline mobile application with the API.<br/>
This endpoint is called when the application shares the diary data.<br/>
You can use it manually if you want to insert the data yourself.

**Example API Request:**<br/>
```bash
curl '<APP_BASE_URL>/api/v1/sync' \
	-H 'Content-Type: application/json' \
	-H "Authorization: ApiKey <YOUR_API_KEY>" \
	-d '{
			"nutrition": {
				"calories": 2350
			},
			"entry": {
				"dateTime": "2024-01-05T00:00:00.000Z"
			}
		}'
```

**HTTP POST JSON Data:**<br/>

| Object               | Description                                       |
|----------------------|---------------------------------------------------|
| `nutrition`          | **[Required]** Nutritional details of the day     |
| `entryDetails`       | Represents details about each specific food entry |
| `entry`              | General entry info with items list                |
| `entry` > `dateTime` | **[Required]** Date and time of the entry         |

!!! note

    As long as you send valid JSON, the data will be inserted in the database, but in order to see the data in other API endpoints you should use the **required** fields. 

**Example API Response:**<br/>
```json
{
	"status": 200,
	"message": "Data synchronized."
}
```

**API Response Objects:**<br/>

| Response Object | Description                                            |
|-----------------|--------------------------------------------------------|
| `status`        | HTTP status code                                       |
| `message`       | Return `'Data synchronized.'` if the data was inserted |
