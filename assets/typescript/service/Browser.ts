// import {OnLoadInterface} from "./OnLoadInterface";

export enum Method {
    POST = "POST",
    GET = "GET",
    PUT = "PUT",
    DELETE = "DELETE",
    PATCH = "PATCH",
    HEAD = "HEAD",
}

/**
 * Content-Types
 */
export enum ContentType {
    blob = "blob",
    json = "application/json",
    text = "text/plain",
    html = "text/html"
}

class O2QS {

    public static add(fd: FormData, key: string, value: any): void {
        value = (typeof value == 'function') ? value() : (value == null ? "" : value);
        fd.append(key, value);
    }

    public static build(fd: FormData, key: string, value: any) {
        if (value instanceof Array) {
            let i = 0;
            const t = /\[\]$/;
            const l = value.length;
            for (; i < l; i++) {
                if (t.test(key)) {
                    O2QS.add(fd, key, value[i]);
                } else {
                    O2QS.build(fd, key + "[" + (typeof value[i] === "object" ? i : "") + "]", value[i]);
                }
            }
        } else if (typeof value == "object") {
            for (const okey in value) {
                O2QS.build(fd, key + "[" + okey + "]", value[okey]);
            }
        } else {
            O2QS.add(fd, key, value);
        }
    }

    public static toFD(obj: { [key: string]: any } | any[]): FormData {
        const fd = new FormData();
        if (obj instanceof Array) {
            for (const idx in obj) {
                this.add(fd, idx, obj[idx]);
            }
        } else {
            for (const key in obj) {
                O2QS.build(fd, key, obj[key]);
            }
        }
        console.log(fd);
        return fd;
    }
}

// const subscriberOnLoad: OnLoadInterface[] = [];

export class Browser {

    // public static subscribeOnLoad(subscriber: OnLoadInterface) {
    //     if (subscriberOnLoad.indexOf(subscriber) === -1) {
    //         subscriberOnLoad.push(subscriber);
    //     }
    // }
    //
    // public static unsubscribeOnLoad(subscriber: OnLoadInterface) {
    //     if (subscriberOnLoad.indexOf(subscriber) !== -1) {
    //         subscriberOnLoad.splice(subscriberOnLoad.indexOf(subscriber), 1);
    //     }
    // }

    /**
     * @param url // string
     * @param toAdd // URLSearchParams
     */
    public static joinQs(url: string, toAdd: URLSearchParams) {
        const spl = url.split("?");
        if (spl.length === 1) {
            const qs = toAdd.toString();
            return spl[0] + (qs ? "?" + qs : "");
        }
        const p = new URLSearchParams(spl[1]);
        let hash = "";
        if (typeof spl[1] !== "undefined") {
            const help = spl[1].split("#");
            hash = help.length === 2 ? "#" + help[1] : "";
        }

        toAdd.forEach((value, key) => {
            p.set(key, value);
        });
        return spl[0] + "?" + p.toString() + hash;
    }

    /**
     *
     * @param response
     * @param type
     */
    public static async responseToType(response: Response, type: ContentType | string): Promise<any> {
        try {
            switch (type) {
                case "json":
                case ContentType.json:
                    return response.json();
                case  "text":
                case  "html":
                case ContentType.text:
                case ContentType.html:
                    return response.text();
                case  "blob":
                    return response.blob();
                default:
                    return Promise.reject(new Error(`The response Content-Type "${type}" is not supported`));
            }
        } catch (e: any) {
            return Promise.reject({
                title: e.message,
                status: response.status,
                instance: "ContentType"
            });
        }
    }

    /**
     *
     * @param test
     */
    public static isToStringify(test: any | any[]) {
        return !(typeof test === "string" || test instanceof String || test instanceof Blob || test instanceof FormData);
    }

    /**
     *
     * @param data
     */
    public static stringify(data: any) {
        return JSON.stringify(data !== undefined ? data : "undefined");
    }

    /**
     *
     * @param data
     */
    public static toFormData(data: any) {
        const formData = new FormData();
        for (const key of Object.keys(data)) {
            formData.append(key, Browser.isToStringify(data[key]) ? Browser.stringify(data[key]) : data[key]);
        }
        return formData;
    }

    private static checkContentType(response: Response, requested?: ContentType) {
        // error as json
        return requested === undefined || (requested.toLowerCase() === ContentType.json && requested.toLowerCase() === Browser.getContentType(response)) || requested.toLowerCase() === Browser.getContentType(response);
    }

    private static getContentType(response: Response) {
        const respType: string[] = (response.headers.get("Content-Type") ?? "").split(";");
        return (respType[0] as string).trim().toLowerCase();
    }

    /**
     *
     * @param uri
     * @param method
     * @param data
     * @param responseType
     * @param showLoading
     */
    public static async fetch(
        uri: string,
        method: Method | string,
        data: string | { [key: string]: any } | any[] | FormData | null = null,
        responseType?: ContentType,
        showLoading: boolean = true): Promise<any> {
        let _uri = uri;
        let _data = null;
        if (method.toLowerCase() === 'get') {
            if (data === null) {
                _data = null;
            } else if (data instanceof FormData) {
                _uri = Browser.joinQs(uri, new URLSearchParams(data as any))
            } else if (data instanceof URLSearchParams) {
                _uri = Browser.joinQs(uri, data);
            } else if (typeof data === "object") {
                _uri = Browser.joinQs(uri, new URLSearchParams(O2QS.toFD(data) as any));
            }
        } else {
            if (data === null || typeof data === "string" || data instanceof String || data instanceof URLSearchParams ||
                data instanceof FormData || data instanceof Blob || data instanceof ArrayBuffer ||
                data instanceof ReadableStream) {
                _data = data;
            } else if (typeof data === "object") {
                _data = O2QS.toFD(data);
            }
        }
        const init: RequestInit = {
            method: method,
            body: _data as any
        }
        const response: Response = await fetch(_uri, init);
        if (!response.ok) {
            return Promise.reject(response);
        } else if (Browser.getContentType(response) === ContentType.json) {
            return Browser.responseToType(response, ContentType.json)
                .then((result: any) => {
                    if (result.title && result.status && (result.status < 200 || result.status >= 300)) {
                        return Promise.reject(result);
                    } else if (responseType === ContentType.json) {
                        return Promise.resolve(result);
                    } else if (responseType) {
                        return Browser.responseToType(response, responseType)
                    } else {
                        return response;
                    }
                })
                .catch((reason: any) => {
                    return Promise.reject(reason);
                });
        } else if (responseType) {
            return Browser.responseToType(response, responseType)
                .then((result: any) => {
                    if (responseType === ContentType.json) {
                        if (result.title && result.status && (result.status < 200 || result.status >= 300)) {
                            return Promise.reject(result);
                        }
                    }
                    return Promise.resolve(result);
                })
                .catch((reason: any) => {
                    return Promise.reject(reason);
                });
        } else {
            return response;
        }
    }

    /**
     *
     * @param uri
     * @param method
     * @param data
     * @param responseType
     * @param showLoading
     */
    public static async fetchContent(
        uri: string,
        method: Method,
        data?: { [key: string]: any } | any[] | FormData, // | GeoJSONFeatureCollection,
        responseType?: ContentType,
        showLoading: boolean = true): Promise<any> {
        const _data = (typeof data === "string" || data === null) ? data : JSON.stringify(data);
        return Browser.fetch(uri, method, _data, responseType, showLoading);
    }
}
//  const form: HTMLFormElement;
// form.