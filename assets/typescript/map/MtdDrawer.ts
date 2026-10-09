import VectorLayer from "ol/layer/Vector";
import Draw from "ol/interaction/Draw";
import Style from "ol/style/Style"
import Vector from "ol/source/Vector";
import {boundingExtent, getBottomLeft, getBottomRight, getTopLeft, getTopRight} from "ol/extent";
import Polygon from "ol/geom/Polygon"

export enum SHAPES {
    NONE = "NONE",
    BOX = "BOX",
    POLYGON = "POLYGON"
}

export class MtdDrawer {
    protected layer: VectorLayer;
    protected interaction: Draw|undefined;

    constructor(layer: VectorLayer) {
        this.layer = layer;
    }

    public getLayer(): VectorLayer {
        return this.layer;
    }

    public getInteraction(): Draw|undefined {
        return this.interaction;
    }

    public setInteraction(type: SHAPES, drawStyle: Style) {
        switch (type) {
            case SHAPES.BOX:
                this.interaction = new Draw({
                    source: (this.layer as VectorLayer).getSource() as Vector,
                    type: 'Circle',
                    style: drawStyle,
                    geometryFunction: createBox() // ol.d.ts has no function "ol.interaction.Draw.createBox()"
                });
                break;
            case SHAPES.POLYGON:
                this.interaction = new Draw({
                    source: (this.layer as VectorLayer).getSource() as Vector,
                    type: 'Polygon',
                    style: drawStyle
                });
                break;
            default:
                this.interaction = undefined;
        }
    }
}

/**
 * ol.d.ts has no function "ol.interaction.Draw.createBox()"
 * @returns {(coordinates:any, opt_geometry:any)=>any|ol.geom.Polygon}
 */
export function createBox(): any {
    return (
        (coordinates: any, opt_geometry: any)=> {
            var extent = boundingExtent(coordinates);
            var geometry = opt_geometry || new Polygon([]);
            geometry.setCoordinates([[
                getBottomLeft(extent),
                getBottomRight(extent),
                getTopRight(extent),
                getTopLeft(extent),
                getBottomLeft(extent)
            ]]);
            return geometry;
        }
    );
}