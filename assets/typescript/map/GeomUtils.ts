import proj4 from "proj4";
import {register} from "ol/proj/proj4";
import Projection from "ol/proj/Projection";
import {METERS_PER_UNIT, get as getProjection} from 'ol/proj';
import Style from "ol/style/Style";
import Fill from "ol/style/Fill";
import Stroke from "ol/style/Stroke";
import Circle from "ol/style/Circle";

export class GeomUtils {

    public static sridToEpsg(srid: number): string {
        return "EPSG:" + srid;
    }

    public static epsgToSrid(code: string): number | undefined {
        const chunks: string[] = code.split(":");
        return chunks.length > 1 ? parseInt((chunks[chunks.length - 1] as any) as string, 10) : undefined;
    }

    public static calculateMeterPerUnits(units: 'degrees'|'m'|'ft'|'radians'|'pixels'|'tile-pixels'|'us-ft') {
        return METERS_PER_UNIT[units as 'degrees'|'m'|'ft'|'radians'|'us-ft'];
        // let meterPerUnit: number;
        // if (units === 'degrees') {
        //     meterPerUnit = olProj.METERS_PER_UNIT['degrees'];
        // } else {
        //     meterPerUnit = olProj.METERS_PER_UNIT['m'];
        // }
        // return meterPerUnit;
    }

    public static initProj4Defs(proj4Defs: { [epsg: string]: any }): void {
        for (const name in proj4Defs) {
            if (name) {
                GeomUtils.setProj4Def(name, proj4Defs[name] as string);
            }
        }
        register(proj4);
    }

    public static setProj4Def(name: string, definition: string) {
        proj4.defs(name, definition);
    }

    public static getProjection(name: string) {
        return getProjection(name);
    }

    public static createProjection(name: string, extent?: [number, number, number, number]): Projection | undefined {
        const p = GeomUtils.getProjection(name);
        if (p === null) {
            return undefined;
        }
        const proj = new Projection({
            code: name,
            units: p.getUnits(),
        });
        if (extent !== undefined) {
            proj.setExtent(extent);
        }
        return proj;
    }

    public static resolutionScaleFactor(units: 'degrees'|'m'|'ft'|'radians'|'us-ft'): number {
        const dpi = 25.4 / 0.28; // dpi for WMTS
        const inchesPerMeter = 39.37;
        const mpu = METERS_PER_UNIT[units as 'degrees'|'m'|'ft'|'radians'|'us-ft'];
        return mpu * inchesPerMeter * dpi;
    }


    public static resolutionForScale(scale: number, factor: number): number {
        return scale / factor;
    }

    public static resolutionsForScales(scales: number[], units: 'degrees'|'m'|'ft'|'radians'|'us-ft'): number[] {
        let resolutions = [];
        let factor = GeomUtils.resolutionScaleFactor(units);
        for (let i = 0; i < scales.length; i++) {
            resolutions.push(GeomUtils.resolutionForScale(scales[i] as number, factor));
        }
        return resolutions;
    }
    //
    // public static scaleToResolution(scale: number|number[], meterPerUnit: number): number|number[] {
    //     if (scale instanceof Array) {
    //         const resolution: number[] = [];
    //         for (const sc of scale) {
    //             resolution.push(GeomUtils.scaleToResolution(sc, meterPerUnit) as number);
    //         }
    //         return resolution;
    //     } else {
    //         console.log(scale, GeomUtils.resolutionScaleFactor(meterPerUnit));
    //         return 1000 * scale / GeomUtils.resolutionScaleFactor(meterPerUnit);
    //     }
    // }
    //
    // public static resolutionToScale (resolution: number|number[], meterPerUnit: number): number|number[] {
    //     if (resolution instanceof Array) {
    //         const scales: number[] = [];
    //         for (const res of resolution) {
    //             scales.push(GeomUtils.resolutionToScale(res, meterPerUnit) as number);
    //         }
    //         return scales;
    //     } else {
    //         return resolution * GeomUtils.resolutionScaleFactor(meterPerUnit);
    //     }
    // }

    public static getStyle(options: any, style?: Style): Style {
        let style_ = style ? style : new Style();
        style_.setFill(new Fill(options['fill']));
        style_.setStroke(new Stroke(options['stroke']));
        if (options['image'] && options['image']['circle']) {
            style_.setImage(new Circle({
                    radius: options['image']['circle']['radius'],
                    fill: new Fill({
                        color: options['image']['circle']['fill']['color']
                    })
                }
            ));
        }
        return style_;
        //
        // return new ol.style_.Style({
        //     fill: new ol.style_.Fill(options['fill']),
        //     stroke: new ol.style_.Stroke(options['stroke'])//,
        //     // image: new ol.style_.Circle({
        //     //     radius: 7,
        //     //     fill: new ol.style_.Fill(options['fill'])
        //     // })
        // });
    }


    //
    // public static pathToUri(tocheck: string): string {
    //     // if (tocheck.startsWith("http")) { // TODO for others protocols?
    //     return tocheck;
    //     // } else {
    //     //     return AppService.inst.getBaseUrl() + AppService.inst.getBasePath() + tocheck;
    //     // }
    // }
    //
    // public static addCollectionToLayer(vector: VectorLayer<any>, collection: GeoJSONFeatureCollection, clearBefore: boolean): void {
    //     if (clearBefore) {
    //         vector.getSource().clear();
    //     }
    //     const geoJSON = new GeoJSON();
    //     for (const feature of collection.features) {
    //         vector.getSource().addFeature(geoJSON.readFeature(feature));
    //     }
    // }
    //
    // public static toPolygon(geometry: GeoJSONMultiPolygon | GeoJSONPolygon | GeoJSONPolygon[]): Polygon[] | undefined {
    //     if (Utils.isArray(geometry)) {
    //         return geometry.map((polygon: GeoJSONPolygon) => new GeoJSON().readGeometry(polygon));
    //     }
    //     const geom = new GeoJSON().readGeometry(geometry) as Geometry;
    //     if (geom instanceof MultiPolygon) {
    //         return (geom as MultiPolygon).getPolygons();
    //     } else if (geom instanceof Polygon) {
    //         return [geom as Polygon];
    //     } else {
    //         return undefined;
    //     }
    // }
    //
    // public static toFeatures(features: Feature[]): GeoJSONFeatureCollection {
    //     return new GeoJSON().writeFeaturesObject(features);
    // }
    //
    // public static toCollection(features: GeoJSONFeature[] = []): GeoJSONFeatureCollection {
    //     return {
    //         type: "FeatureCollection",
    //         features: features
    //     }
    // }
}
