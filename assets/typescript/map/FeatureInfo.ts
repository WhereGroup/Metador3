import {dom} from './dom';
import {TITLE, UUID} from "./MtdMap";
import {default as OlMap} from "ol/Map";
import {Coordinate} from "ol/coordinate";
import {Size} from "ol/size";
import Overlay from "ol/Overlay";
import VectorLayer from "ol/layer/Vector";
import Select from "ol/interaction/Select";
import Control from "ol/control/Control";
import Feature from "ol/Feature";
import Geometry from "ol/geom/Geometry";
import MapBrowserEvent from "ol/MapBrowserEvent";

export class FeatureInfo {
    private static itemTagName: string = 'span';
    private olMap?: OlMap;
    private tooltip?: Overlay;
    private tooltipCoord?: Coordinate;
    private tooltipElm?: HTMLElement;
    private tooltipContent?: HTMLElement;
    private layer: VectorLayer|undefined;
    private select?: Select;
    private button: HTMLButtonElement;

    constructor(map: OlMap, layer?: VectorLayer) {
        this.olMap = map;
        this.layer = layer;
        this.button = <HTMLButtonElement>dom.create("button", {
            type: "button",
            title: "FeatureInfo aktivieren"
        }, ["icon-info-circle"]);
        this.button.addEventListener('click', this.buttonClick.bind(this), false);
        const wrapper = dom.create("div", {}, ["feature-info", "ol-unselectable", "ol-control"]);
        wrapper.appendChild(this.button);
        this.olMap.addControl(
            new Control(
                {
                    element: wrapper
                }
            )
        );
    }

    private buttonClick(e: any) {
        if (!dom.hasClass(e.target, 'success')) {
            dom.addClass(e.target, "success");
            this.activate();
        } else {
            dom.removeClass(e.target, "success");
            this.deactivate();
        }
    }

    private activateSelect() {
        if (!this.select) {
            const fi = this;
            let timestamp: number = 0;
            this.select = new Select({
                multi: true,
                layers: [this.layer as VectorLayer],
                filter: function (feature: Feature) {
                    timestamp = Date.now() + 5;
                    setTimeout(function () {
                        if (Date.now() >= timestamp) {
                            if (fi.tooltipElm) {
                                fi.showTooltip();
                            }
                        }
                    }, 5);
                    return true;
                }
            });
            this.select.on('select', function (e) {
                if (e.target.getFeatures().getLength() === 0) {
                    if (fi.tooltipElm) {
                        fi.showTooltip();
                    }
                }
            });

        }
        this.select.getFeatures().clear();
        this.olMap?.addInteraction(this.select);
    }

    private deactivateSelect() {
        this.select?.getFeatures().clear();
        this.olMap?.removeInteraction(this.select as Select);
    }

    reset() {
        if (this.select) {
            this.select.getFeatures().clear();
        }
        if (this.tooltipElm) {
            dom.addClass(this.tooltipElm, 'hidden');
        }
        search.clearMetadataMarks();
    }

    selectFeatures(uuids: string[]) {
        const fi = this;
        if (this.layer && this.select) {
            this.reset();
            this.layer.getSource()?.forEachFeature(
                function (feature: Feature) {
                    for (const uuid of uuids) {
                        if (feature.get(UUID) === uuid) {
                            fi.select?.getFeatures().push(feature);
                        }
                    }
                }
            )
            if (this.tooltipElm) {
                this.showTooltip();
            }
        }
    }

    activate() {
        this.olMap?.on('click', this.setTooltipPosition);
        this.tooltipElm = dom.create("div", {}, ["tooltip", "hidden"]);
        this.tooltipElm.appendChild(dom.create("span", {}, ["icon-cross"]));
        this.tooltipElm.addEventListener('click', this.itemClick.bind(this), false);
        this.tooltipContent = dom.create("div", {}, ["content"]);
        const mapSize = this.olMap?.getSize() as Size;
        this.tooltipContent.style.maxHeight = Math.round(mapSize[1] as number * 0.6) + "px";
        this.tooltipContent.style.maxWidth = Math.round(mapSize[0] as number * 0.6) + "px";
        this.tooltipElm.appendChild(this.tooltipContent);
        this.tooltip = new Overlay({
            element: this.tooltipElm,
            offset: [0, -6],
            positioning: 'bottom-center',
            autoPan: true,

        });
        this.olMap?.addOverlay(this.tooltip);
        this.activateSelect();
    }

    deactivate() {
        this.deactivateSelect();
        search.clearMetadataMarks();
        this.tooltipElm?.removeEventListener('click', this.itemClick.bind(this));
        this.tooltipElm?.remove();
        this.olMap?.removeOverlay(this.tooltip as Overlay);
        this.olMap?.un('click', this.setTooltipPosition);
    }

    private itemClick(e: Event) {
        if ((<any>e.target).tagName === FeatureInfo.itemTagName.toUpperCase()) {
            let tag = (<HTMLElement>e.target);
            if (!dom.hasClass(tag, '-js-tooltip-item')) {
                this.select?.getFeatures().clear();
                this.unSelectDataset();
                dom.addClass(this.tooltipElm as HTMLElement, 'hidden');
            } else {
                dom.removeClass(tag.parentElement as HTMLElement, 'selected', 'span');
                dom.addClass(tag, 'selected');
                this.select?.getFeatures().clear();
                this.unSelectDataset();
                const feature = this.layer?.getSource()?.getFeatureById(tag.getAttribute('data-id')??"");
                this.select?.getFeatures().push(feature);
                this.selectDataset(feature.get(UUID));
            }
        } else {
            e.stopPropagation();
        }
    }

    private setTooltipPosition(en: MapBrowserEvent) {
        this.tooltipCoord = en.coordinate;
    }

    private hideTooltip() {
        if (this.tooltipElm) {
            dom.addClass(this.tooltipElm, 'hidden');
        }
    }

    private showTooltip() {
        const features: Feature<Geometry, { [x: string]: any; }>[] = this.select?.getFeatures().getArray()??[];
        dom.remove('.-js-tooltip-item', this.tooltipElm);
        this.unSelectDataset();
        if (features.length === 0) {
            this.hideTooltip();
        } else if (features.length === 1) {
            this.hideTooltip();
            this.selectDataset((features[0] as Feature).get(UUID));
        } else {
            for (let feature of features) {
                if (!feature.getId()) {
                    feature.setId(feature.get(UUID));
                }
                let attrs = {
                    "title": feature.get(TITLE),
                    "data-uuid": feature.get(UUID),
                    "data-id": feature.getId()
                };
                this.tooltipContent?.appendChild(
                    dom.create(FeatureInfo.itemTagName, attrs, ['-js-tooltip-item', 'selected'], feature.get(TITLE))
                );
                this.selectDataset(feature.get(UUID));
            }
            dom.removeClass(this.tooltipElm as HTMLElement, 'hidden');
            this.tooltip?.setPosition(this.tooltipCoord);
        }
    }

    private selectDataset(selector: string) {
        if (search.markMetadata) {
            search.markMetadata(selector);
        }
    }

    private unSelectDataset(selector?: string) {
        if (selector !== undefined && search.unmarkMetadata) {
            search.unmarkMetadata(selector);
        } else if (selector === undefined && search.clearMetadataMarks) {
            search.clearMetadataMarks();
        }
    }
}

declare var search: any;
