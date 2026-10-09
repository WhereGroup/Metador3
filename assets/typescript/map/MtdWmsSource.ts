import {MtdSource} from "./MtdSource";
import {LAYER_UUID, MtdMap, TITLE, UUID} from "./MtdMap";
import {MapActivity} from "./MapActivity";
import {ProjectionLike} from "ol/proj";
import Base from "ol/layer/Base";
import Layer from "ol/layer/Layer";
import ImageWMS from "ol/source/ImageWMS";
import ImageLayer from "ol/layer/Image"
import ImageSourceEvent from "ol/events/Event"

export class MtdWmsSource implements MtdSource {
    private static _instance: MtdWmsSource;
    private mtdMap: MtdMap;
    private useLoadEvents: boolean;
    public static mapActivity: MapActivity;// = MapActivity.create();
    public disabled: any;

    private constructor(mtdMap: MtdMap, useLoadEvents: boolean = true) {
        this.mtdMap = mtdMap;
        this.useLoadEvents = useLoadEvents;
        if (this.useLoadEvents) {
            MtdWmsSource.mapActivity = MapActivity.create();
        }
        this.disabled = {};
    }

    static create(mtdMap: MtdMap, useLoadEvents: boolean = true): MtdWmsSource {
        if (!MtdWmsSource._instance) {// singleton
            MtdWmsSource._instance = new MtdWmsSource(mtdMap, useLoadEvents);
        }
        return MtdWmsSource._instance;
    }

    public addDisabled(layer: Base) {
        this.disabled[layer.get(UUID)] = layer.get(UUID);
        this.mtdMap.getLayerTree().setDisable(layer, true);
        this.mtdMap.setVisible(layer, false);
    }

    public removeDisabled(layer: Base) {
        if (layer.get(UUID)){
            this.mtdMap.getLayerTree().setDisable(layer, false);
            let visible = this.mtdMap.getLayerTree().getVisible(layer);
            this.mtdMap.setVisible(layer, visible);
            delete this.disabled[layer.get(UUID)];
        }
    }

    createLayer(layerUuid: string, options: any = null, proj: ProjectionLike, visible: boolean, opacity: number): Base {
        let source = this.createSource(layerUuid, options['url'], options['params'], proj);
        let layerWms = this._createLayer(layerUuid, visible, opacity, source, options['title'] ? options['title'] : null);
        return layerWms;
    }

    private _createLayer(layerUuid: string, visible: boolean, opacity: number, source: ImageWMS, title?: string) {
        let layerWms = new ImageLayer({
            source: source,
            visible: visible,
            opacity: opacity
        });
        layerWms.set(UUID, layerUuid);
        if (title) {
            layerWms.set(TITLE, title);
        }
        return layerWms;
    }

    private createSource(layerUuid: string, url: string, params: any, proj: ProjectionLike): ImageWMS {
        let source = new ImageWMS({
            url: url,
            params: params,
            projection: proj
        });
        source.set(LAYER_UUID, layerUuid);
        this.setLoadEvents(source);
        return source;
    }

    reprojectionSource(layer: Base, fromProj: ProjectionLike, toProj: ProjectionLike) {
        const oldsource = (layer as Layer).getSource() as ImageWMS;
        const newSource = this.createSource(layer.get(UUID), oldsource.getUrl() as string, oldsource.getParams(), toProj);
        (<Layer>layer).setSource(newSource);
        this.removeDisabled(layer);
    }

    cloneLayer(layer: Base, fromProj: ProjectionLike, toProj: ProjectionLike): Base {
        let oldsource = (layer as Layer).getSource() as ImageWMS;
        let newSource = this.createSource(layer.get(UUID), oldsource.getUrl() as string, oldsource.getParams(), toProj);
        let oldLayer = (<Layer>layer);
        let newLayer = this._createLayer(oldLayer.get(UUID), oldLayer.getVisible(), oldLayer.getOpacity(), newSource, oldLayer.get(TITLE));
        return newLayer;
    }


    private setLoadEvents(source: ImageWMS) {
        if (this.useLoadEvents) {
            // source.setImageLoadFunction(this.imageLoadFunction.bind(this));
            source.on('imageloadstart', this.imageLoadStart.bind(this));
            source.on('imageloadend', this.imageLoadEnd.bind(this));
            source.on('imageloaderror', this.imageLoadError.bind(this));
        }
    }

    imageLoadStart(e: ImageSourceEvent) {
        if (MtdWmsSource.mapActivity) {
            MtdWmsSource.mapActivity.loadStart((<ImageWMS>e.target).get(LAYER_UUID));
        }
    }

    imageLoadEnd(e: ImageSourceEvent) {
        if (MtdWmsSource.mapActivity) {
            MtdWmsSource.mapActivity.loadEnd((<ImageWMS>e.target).get(LAYER_UUID));
        }
    }

    imageLoadError(e: ImageSourceEvent) {
        if (MtdWmsSource.mapActivity) {
            MtdWmsSource.mapActivity.loadError((<ImageWMS>e.target).get(LAYER_UUID));
        }
        let layer = this.mtdMap.findLayer((<ImageWMS>e.target).get(LAYER_UUID));
        this.addDisabled(layer as Base);
    }

}
