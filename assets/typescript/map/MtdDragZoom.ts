import {dom} from "./dom";
import {default as OlMap} from "ol/Map";
import DragZoom from 'ol/interaction/DragZoom';
import Control from 'ol/control/Control';

export class MtdDragZoom {
    private dragzoom: DragZoom;
    private olMap: OlMap;
    private button: HTMLButtonElement;

    constructor(map: OlMap) {
        this.olMap = map;
        this.dragzoom = new DragZoom({
            condition: function () {
                return true;
            }
        });
        this.button = <HTMLButtonElement>
            dom.create("button", {type: "button", title: "Ausschnitt aufziehen"}, ["icon-map"]);
        this.button.addEventListener('click', this.buttonClick.bind(this), false);
        this.dragzoom.on('boxend', this.deactivate.bind(this));
        const wrapper = dom.create("div", {}, ["drag-zoom", "ol-unselectable", "ol-control"]);
        wrapper.appendChild(this.button);
        this.olMap.addControl(
            new Control(
                {
                    element: wrapper
                }
            )
        );
    }

    private buttonClick(e: Event) {
        if (!dom.hasClass(e.target as any, 'success')) {
            this.activate();
        } else {
            this.deactivate();
        }
    }

    private activate() {
        dom.addClass(this.button, 'success');
        this.olMap.addInteraction(this.dragzoom);
    }

    private deactivate() {
        dom.removeClass(this.button, 'success');
        this.olMap.removeInteraction(this.dragzoom);
    }
}