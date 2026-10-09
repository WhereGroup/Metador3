import Geometry from "ol/geom/Geometry";
import Projection from "ol/proj/Projection";
import {Extent} from "ol/extent";
import {fromExtent} from "ol/geom/Polygon";
import Polygon from 'ol/geom/Polygon';
import {ProjectionLike} from "ol/proj";

export class MtdGeom {
    protected geom: Geometry;
    protected proj: ProjectionLike;

    constructor(geom: Geometry, proj: ProjectionLike) {
        this.geom = geom;
        this.proj = proj;
    }

    getGeom(): Geometry {
        return this.geom;
    }

    getProj(): ProjectionLike {
        return this.proj;
    }

    getExtent(proj: Projection): Extent {
        if (this.proj !== proj) {
            return (<Geometry>(<any> this.geom).clone()).transform(this.proj, proj).getExtent();
        } else {
            return (<Geometry>(<any> this.geom).clone()).getExtent();
        }
    }

    public getPolygonForExtent(proj: Projection) {
        return fromExtent(this.getExtent(proj)) as Polygon;
    }
}
