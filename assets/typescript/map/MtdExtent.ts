import {MtdGeom} from "./MtdGeom";
import MultiPoint from "ol/geom/MultiPoint";
import {ProjectionLike} from "ol/proj";

export class MtdExtent extends MtdGeom {
    public static fromArray(ordinates: [number,number,number,number], proj: ProjectionLike): MtdExtent {
        let geom = new MultiPoint([[ordinates[0], ordinates[1]], [ordinates[2], ordinates[3]]]);
        return new MtdExtent(geom, proj);
    }
}
