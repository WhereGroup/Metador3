export class dom {
    // private static data = data;

    static create(tagname: string, attrs: any = {}, classes: string[] = [], text: string = '', data: any = {}): HTMLElement {
        let element = document.createElement(tagname);
        return dom.add(element, attrs, classes, text, data);
    }

    static add(element: HTMLElement, attrs: any = {}, classes: string[] = [], text: string = '', data: any = {}): HTMLElement {
        for (const key in attrs) {
            element.setAttribute(key, attrs[key]);
        }
        for (const name of classes) {
            element.classList.add(name);
        }
        if (text !== '') {
            element.textContent = text;
        }
        // for (const key in data) {
        //     element.dataset[key] = data[key];
        // }

        return element;
    }

    static delete(element: HTMLElement, attrs: string[] = [], classes: string[] = [], text: boolean = false, data: any = {}): HTMLElement {
        for (const key of attrs) {
            element.removeAttribute(key);
        }
        for (const cl of classes) {
            dom.removeClass(element, cl);
        }
        return element;
    }

    static find(selector: string, context: any = document): NodeListOf<Element>|null {
        if (context instanceof Document) {
            return context.querySelectorAll(selector);
        } else if (context instanceof HTMLElement) {
            return context.querySelectorAll(selector);
        } else {
            return null; // TODO return a blank NodeListOf<Element>
        }
    }

    static findFirst(selector: string, context: any = document): Element|null {
        if (context instanceof Document) {
            return context.querySelector(selector);
        } else if (context instanceof HTMLElement) {
            return context.querySelector(selector);
        } else {
            return null;
        }
    }

    static hasClass(element: HTMLElement, name: string): boolean {
        return element !== null && element.classList.contains(name);
    };

    static addClass(context: HTMLElement, name: string, selector?: string): void {
        if (selector) {
            const list: NodeListOf<Element>|null = dom.find(selector, context);
            for (const item of Array.from(list??[])) {
                item.classList.add(name);
            }
        } else {
            context.classList.add(name);
        }
    };

    /**
     *
     * @param context
     * @param name
     * @returns {Element}
     */
    static removeClass(context: Element, name: string, selector?: string): void {
        if (selector) {
            const list: NodeListOf<Element>|null = dom.find(selector, context);
            for (const item of Array.from(list??[])) {
                item.classList.remove(name);
            }
        } else {
            context.classList.remove(name);
        }
    };

    static remove(selector: string, context: any = document): void { //NodeListOf<Element> | null {
        let list: Element[] = Array.from(dom.find(selector, context)??[]).reverse();
        for (let i = list.length - 1 ; i >= 0; i--) {
            (list[i] as Element).remove();
        }
    };

    static toggleClass(element: Element, name: string): Element | null {
        element.classList.remove(name);
        return element;
    }
}