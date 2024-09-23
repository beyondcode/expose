export function exampleSubdomains(): string[] {
    return Array.from({ length: Math.floor(Math.random() * 3) + 1 }, (_, i) => `https://beyondcode-${i + 1}.share.idontcare.lol`);
}

export function exampleUser(): object {
    return { "can_specify_subdomains": 0 };
}

export function exampleRequests(): ExposeLog[] {
    return [
        {
          "id": "66ec2e13a3b60",
          "performed_at": "2024-09-19 14:00:01",
          "duration": 195,
          "subdomain": "example.share.site.com",
          "request": {
            "raw": "GET / HTTP/1.1\r\nhost: example.share.site.com\r\nuser-agent: GuzzleHttp/7",
            "method": "GET",
            "uri": "/",
            "headers": {
              "Host": "example.share.site.com",
              "User-Agent": "GuzzleHttp/7",
              "x-forwarded-for": "192.168.1.1"
            },
            "body": "",
            "query": [],
            "post": []
          },
          "response": {
            "status": 100,
            "headers": {
              "Server": "nginx/1.21",
              "Content-Type": "application/json"
            },
            "body": "{\"message\": \"OK\"}"
          }
        },
        {
          "id": "77fc2e14b3d61",
          "performed_at": "2024-09-19 14:05:32",
          "duration": 100,
          "subdomain": "test.api.service.com",
          "request": {
            "raw": "POST /api/data HTTP/1.1\r\nhost: test.api.service.com\r\nuser-agent: curl/7.80.1",
            "method": "POST",
            "uri": "/api/data/some/longer/uri/even/longer/now",
            "headers": {
              "Host": "test.api.service.com",
              "User-Agent": "curl/7.80.1",
              "x-forwarded-for": "10.0.0.5"
            },
            "body": "{\"data\": \"test\"}",
            "query": [],
            "post": []
          },
          "response": {
            "status": 201,
            "headers": {
              "Server": "Apache/2.4",
              "Content-Type": "application/json"
            },
            "body": "{\"message\": \"Created\"}"
          }
        },
        {
          "id": "88ed3f25c4e72",
          "performed_at": "2024-09-19 14:12:11",
          "duration": 85,
          "subdomain": "user.profile.example.com",
          "request": {
            "raw": "PUT /user/123 HTTP/1.1\r\nhost: user.profile.example.com\r\nuser-agent: PostmanRuntime/7.29.0",
            "method": "PUT",
            "uri": "/user/123",
            "headers": {
              "Host": "user.profile.example.com",
              "User-Agent": "PostmanRuntime/7.29.0",
              "x-forwarded-for": "172.16.0.2"
            },
            "body": "{\"name\": \"John Doe\"}",
            "query": [],
            "post": []
          },
          "response": {
            "status": 300,
            "headers": {
              "Server": "nginx/1.19",
              "Content-Type": "application/json"
            },
            "body": "{\"message\": \"Updated\"}"
          }
        },
        {
          "id": "99fe4g36d5f83",
          "performed_at": "2024-09-19 14:20:45",
          "duration": 60,
          "subdomain": "files.upload.app.com",
          "request": {
            "raw": "POST /upload HTTP/1.1\r\nhost: files.upload.app.com\r\nuser-agent: Mozilla/5.0",
            "method": "POST",
            "uri": "/upload",
            "headers": {
              "Host": "files.upload.app.com",
              "User-Agent": "Mozilla/5.0",
              "x-forwarded-for": "203.0.113.5"
            },
            "body": "{\"file\": \"data\"}",
            "query": [],
            "post": []
          },
          "response": {
            "status": 404,
            "headers": {
              "Server": "Apache/2.4",
              "Content-Type": "application/json"
            },
            "body": "{\"message\": \"File uploaded\"}"
          }
        },
        {
          "id": "a0gf5h47e6g94",
          "performed_at": "2024-09-19 14:30:12",
          "duration": 140,
          "subdomain": "auth.login.api.com",
          "request": {
            "raw": "POST /auth/login HTTP/1.1\r\nhost: auth.login.api.com\r\nuser-agent: Insomnia/2022.4",
            "method": "POST",
            "uri": "/auth/login",
            "headers": {
              "Host": "auth.login.api.com",
              "User-Agent": "Insomnia/2022.4",
              "x-forwarded-for": "198.51.100.1"
            },
            "body": "{\"username\": \"user\", \"password\": \"pass\"}",
            "query": [],
            "post": []
          },
          "response": {
            "status": 500,
            "headers": {
              "Server": "nginx/1.20",
              "Content-Type": "application/json"
            },
            "body": "{\"token\": \"abcd1234\"}"
          }
        }
      ]
}