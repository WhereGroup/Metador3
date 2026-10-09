import {MtdSource} from "./MtdSource";
import {MtdMap, UUID} from "./MtdMap";
import {ProjectionLike} from "ol/proj";
import Base from "ol/layer/Base";
import VectorLayer from "ol/layer/Vector";
import Vector from "ol/source/Vector";
import {GeoJSON} from "ol/format";
import Layer from "ol/layer/Layer";
import Feature from "ol/Feature";

export class MtdVectorSource implements MtdSource {
    private static _instance: MtdVectorSource;
    protected showable: boolean = false;
    private mtdMap: MtdMap;

    private constructor(mtdMap: MtdMap) {
        // super(false);
        this.mtdMap = mtdMap;
        // this.setShowable(false);
    }

    static create(mtdMap: MtdMap): MtdVectorSource {
        if (!MtdVectorSource._instance) {// singleton
            MtdVectorSource._instance = new MtdVectorSource(mtdMap);
        }
        return MtdVectorSource._instance;
    }

    createLayer(layerUuid: string, options: any, proj: ProjectionLike, visible: boolean = true, opacity: number = 1.0): Base {
        let vLayer = new VectorLayer({
            source: new Vector({wrapX: false}),
            style: options['style']
        });
        vLayer.set(UUID, layerUuid);
        return vLayer;
    }

    reprojectionSource(layer: Base, fromProj: ProjectionLike, toProj: ProjectionLike) {
        let source = (layer as Layer).getSource();
        let features: Feature[] = (source as Vector).getFeatures();
        for (let feature of features) {
            feature.setGeometry(feature.getGeometry()?.transform(fromProj, toProj));
        }
    }

    cloneLayer(layer: Base, fromProj: ProjectionLike, toProj: ProjectionLike): Base {
        /* TODO for clone */
        return layer;
    }

    showFeatures(vLayer: VectorLayer, geoJson: Object) {
        let geoJsonReader: GeoJSON = new GeoJSON();
        let dataproj = geoJsonReader.readProjection(geoJson);
        let features = geoJsonReader.readFeatures(
            geoJson,
            {
                'dataProjection': geoJsonReader.readProjection(geoJson),
                'featureProjection': this.mtdMap.getProjection()
            });
        vLayer.getSource()?.addFeatures(features);
    }

    clearFeatures(vLayer: VectorLayer) {
        vLayer.getSource()?.clear(true);
    }
}
