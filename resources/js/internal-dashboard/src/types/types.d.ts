declare interface RequestData {
    raw: string;
    method: string;
    uri: string;
    headers: {
      Host: string;
      "User-Agent": string;
      "x-forwarded-for"?: string;
      "x-forwarded-proto"?: string;
    };
    body?: string;
    query: any[];
    post: any[];
  }

  declare interface ResponseData {
    status: number;
    headers: {
      Server: string;
      "Content-Type": string;
    };
    body: string;
  }

  declare interface ExposeRequest {
    id: string;
    performed_at: string;
    duration: number;
    subdomain: string;
    request: RequestData;
    response: ResponseData;
  }