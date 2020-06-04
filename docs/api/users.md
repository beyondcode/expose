---
title: Users
order: 4
---

# Users

Expose provides three API endpoints that allow you to either retrieve all registered users, create a new user, or delete an existing user from the expose server.

## Retrieving all users

To retrieve the users, you can perform a GET request to the `/api/users` endpoint:

The result looks like this:

```json
{
   "users":[
	  {
		 "id":9,
		 "name":"miguel",
		 "auth_token":"858fad3d-2163-4af6-8c8d-68e89f80cf8c",
		 "created_at":"2020-06-04 19:31:26",
		 "updated_at":null
	  },
	  {
		 "id":8,
		 "name":"sebastian",
		 "auth_token":"360461ea-23b9-422e-bc76-7ca1b2ec8a91",
		 "created_at":"2020-06-04 19:31:17",
		 "updated_at":null
	  },
	  {
		 "id":7,
		 "name":"marcel",
		 "auth_token":"b5f3ee57-1e77-4a94-8b7f-da13e3dc6478",
		 "created_at":"2020-06-04 19:31:16",
		 "updated_at":null
	  }
   ]
}
```

## Creating a new user

To create a new user on the expose server, you can perform a POST request to the `/api/users` endpoint.

The endpoint expects you to send the following data:

```json
name: STRING
```

This will return a response containing the generated user:

```json
{
	"user": {
		"id":8,
		"name":"sebastian",
		"auth_token":"360461ea-23b9-422e-bc76-7ca1b2ec8a91",
		"created_at":"2020-06-04 19:31:17",
		"updated_at":null
	}
}
```

## Deleting a user

To delete a user on the expose server, you can perform a DELETE request to the `/api/users/{user_id}` endpoint.

> **Note:** The users currently active shared sites will not be disconnected automatically.