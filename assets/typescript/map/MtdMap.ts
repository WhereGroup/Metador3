import {ProjectionLike} from "ol/proj";
import {default as OlMap} from "ol/Map";
import {LayerTree} from './LayerTree';
import {MtdDragZoom} from './MtdDragZoom';
import {FeatureInfo} from "./FeatureInfo";
import {MtdExtent} from "./MtdExtent";
import {MtdDrawer, SHAPES} from "./MtdDrawer";
import {MtdWmsSource} from "./MtdWmsSource";
import {MtdVectorSource} from "./MtdVectorSource";
import {MtdGeom} from "./MtdGeom";
import {Extent} from "ol/extent";
import * as olProj from 'ol/proj';
import GeoJSON, {GeoJSONFeature} from "ol/format/GeoJSON"
import VectorLayer from "ol/layer/Vector";
import LayerGroup  from "ol/layer/Group";
import ImageLayer from "ol/layer/Image"
import {Collection, View} from "ol";
import Base from "ol/layer/Base";
import {Size} from "ol/size";
import DoubleClickZoom from "ol/interaction/DoubleClickZoom";
import SimpleGeometry from "ol/geom/SimpleGeometry";
import MultiPoint from "ol/geom/MultiPoint";
import {defaults as control_defaults} from "ol/control/defaults";
import {defaults as interaction_defaults} from "ol/interaction/defaults";
import ScaleLine from "ol/control/ScaleLine";
import ZoomToExtent from "ol/control/ZoomToExtent";
import MousePosition from "ol/control/MousePosition";
import DragZoom from "ol/interaction/DragZoom";
import ImageWMS from "ol/source/ImageWMS";
import {GeomUtils} from "./GeomUtils";

export const UUID: string = 'uuid';
export const LAYER_UUID: string = 'layeruuid';
export const TITLE: string = 'title';
export const METADOR_EPSG: ProjectionLike = 'EPSG:4326';
export const LAYER_VECTOR = 'vector';
export const LAYER_IMAGE = 'image';

export class MtdMap {
    private static _uuid = 0;
    private static _instance?: MtdMap; // singleton
    private olMap: OlMap;
    private scales: number[];
    private startExtent: MtdExtent;  // xmin, ymin, xmax, ymax options['startExtent']
    private maxExtent: MtdExtent;
    private drawer: MtdDrawer;
    private wmsSource: MtdWmsSource;
    private vecSource: MtdVectorSource;
    private layertree: LayerTree;
    private styles: {[key: string]: any};
    private hgLayer?: VectorLayer;
    private dragzoom?: MtdDragZoom;
    private featureInfo: FeatureInfo;

    private static getUuid(prefix: string = ''): string {
        return prefix + (++MtdMap._uuid);
    }

    private constructor(options: any) { // singleton
        // ol['ENABLE_RASTER_REPROJECTION'] = false;
        GeomUtils.initProj4Defs(options['proj4Defs']);
        this.layertree = LayerTree.create(this);
        this.wmsSource = MtdWmsSource.create(this, true);
        this.vecSource = MtdVectorSource.create(this);
        (<HTMLFormElement>document.querySelector('.-js-crs-code')).value = options['view']['projection'];
        let proj: olProj.Projection = olProj.get(options['view']['projection']) as olProj.Projection;
        this.styles = options['styles'];
        this.scales = options['view']['scales'];
        this.startExtent = MtdExtent.fromArray(options['view']['startExtent'], proj);
        this.maxExtent = MtdExtent.fromArray(options['view']['maxExtent'], proj);
        let interactions = interaction_defaults(
            {
                altShiftDragRotate: false,
                pinchRotate: false
            }
        );
        let controls = control_defaults({attribution: false});//.extend([attribution])
        this.olMap = new OlMap({
            interactions: interactions,
            target: options['map']['target'],
            // renderer: 'canvas',
            controls: controls
        });
        this.olMap?.setView(
            this.createView(
                proj,
                this.maxExtent.getExtent(proj),
                (GeomUtils.resolutionsForScales(this.scales, proj.getUnits() as 'degrees'|'m'|'ft'|'radians'|'us-ft') as number[]).reverse()
            )
        );
        /* make a group layer for all image layers (WMS etc.)*/
        let imageGroup = new LayerGroup({
            layers: new Collection<Base>()
        });
        imageGroup.set(UUID, LAYER_IMAGE)
        this.olMap?.addLayer(imageGroup);
        /* make a group layer for all vector layers (Hightlight, Search results etc.)*/
        let vectorGroup = new LayerGroup({
            layers: new Collection<Base>()
        });
        vectorGroup.set(UUID, LAYER_VECTOR)
        this.olMap?.addLayer(vectorGroup);

        for (let sourceOptions of options['source']) {
            if (sourceOptions['type'] === 'WMS') {
                let wmsLayer = this.addLayer(
                    this.wmsSource.createLayer(
                        MtdMap.getUuid('olay-'),
                        sourceOptions,
                        this.olMap?.getView().getProjection(),
                        sourceOptions['visible'],
                        parseFloat(sourceOptions['opacity'])
                    ), true
                );
            } else {
                console.error(sourceOptions['type'] + ' is not supported.');
            }
        }
        this.olMap?.addControl(new ScaleLine());
        // // zoom to max extent
        // icon.className = "icon-earth";
        // this.olMap?.addControl(new ol.control.ZoomToExtent({
        //     extent: this.maxExtent.getExtent(proj),
        //     label: icon,
        //     tipLabel: "Zoom auf gesamte Ausdehnung"
        // }));
        // zoom to start extent
        let icon = document.createElement('span');
        icon = document.createElement('span');
        icon.className = "icon-home";
        this.olMap?.addControl(new ZoomToExtent({
            extent: this.startExtent.getExtent(proj),
            label: icon,
            tipLabel: "Zoom auf die Start-Ausdehnung"
        }));
        this.olMap?.addInteraction(new DragZoom());
        const control: any = options['map']['control'];
        if (control && control.mousePosition) {
            const posOptions = control.mousePosition;
            const mousePosOptions: any = {
                coordinateFormat: (coordinates: [number,number]) => {
                    return coordinates[0].toFixed(posOptions.fractionDigit) + ', ' + coordinates[1].toFixed(posOptions.fractionDigit);
                }
            };
            this.olMap?.addControl(new MousePosition(mousePosOptions));
        }
        let mapsize = this.olMap?.getSize() as Size;
        if (mapsize[0] !== 0 && mapsize[1]) {
            this.zoomToExtent(this.startExtent.getPolygonForExtent(proj));
        }
        let hgl = this.vecSource.createLayer(
            MtdMap.getUuid('olay-'),
            {'style': GeomUtils.getStyle(this.styles['highlight'])},
            this.olMap?.getView().getProjection()
        );
        this.hgLayer = <VectorLayer>this.addLayer(hgl);

        let vLayer = <VectorLayer>this.addLayer(
            this.vecSource.createLayer(
                MtdMap.getUuid('olay-'),
                {'style': GeomUtils.getStyle(this.styles['search'])},
                this.olMap?.getView().getProjection()
            )
        );
        // vLayer.setMap(this.olMap);
        this.drawer = new MtdDrawer(vLayer);
        this.dragzoom = new MtdDragZoom(this.olMap as OlMap);
        this.featureInfo = new FeatureInfo(this.olMap, this.hgLayer);
    }

    activateFeatureInfo(): void {
        this.featureInfo.activate();
    }

    deactivateFeatureInfo(): void {
        this.featureInfo.deactivate();
    }

    resetFeatureInfo() {
        this.featureInfo.reset();
    }

    selectFeatures(uuids: string[]) {
        this.featureInfo.selectFeatures(uuids);
    }

    getLayerTree(): LayerTree {
        return this.layertree;
    }

    private addIntoLayerTree(layer: Base) {
        if (this.layertree) {
            this.layertree.add(layer);
        }
    }

    private createView(proj: olProj.Projection, extent: Extent, resolutions: number[]) {
        return new View({
            projection: proj,
            resolutions: resolutions,
            extent: extent
        });
    }

    zoomToExtent(geometry: SimpleGeometry | Extent) {
        // this.olMap?.getView().fit(geometry, <olx.view.FitOptions>this.olMap?.getSize());
        this.olMap?.getView().fit(geometry, {size: this.olMap?.getSize()});
    }

    static create(options: any): MtdMap {
        if (!MtdMap._instance) {// singleton
            MtdMap._instance = new MtdMap(options);
        }
        return MtdMap._instance;
    }

    getProjection(): olProj.Projection {
        return this.olMap?.getView().getProjection() as olProj.Projection;
    }

    getDrawer(): MtdDrawer {
        return this.drawer;
    }

    getHgLayer(): VectorLayer|unknown {
        return this.hgLayer;
    }

    /**
     * Adds a layer into a map.
     * @param options
     */
    addLayerForOptions(options: any) {
        if (options['type'] === 'WMS') {
            let wmsLayer = this.addLayer(
                this.wmsSource.createLayer(
                    MtdMap.getUuid('olay-'),
                    options,
                    this.olMap?.getView().getProjection(),
                    options['visible'],
                    parseFloat(options['opacity'])
                ),
                true
            );
        } else {
            console.error(options['type'] + ' is not supported.');
        }
    }

    addLayer(layer: Base, addToLayertree: boolean = false): Base|undefined {
        if (layer instanceof ImageLayer) {
            let group: LayerGroup = <LayerGroup>this.findLayer(LAYER_IMAGE);
            group.getLayers().insertAt(group.getLayers().getLength(), layer);
        } else if (layer instanceof VectorLayer) {
            let group: LayerGroup = <LayerGroup>this.findLayer(LAYER_VECTOR);
            group.getLayers().insertAt(group.getLayers().getLength(), layer);
        } else {
            return undefined;
        }
        if (addToLayertree) {
            this.addIntoLayerTree(layer);
        }
        return layer;
    }

    removeLayer(layer: Base): void {
        this.olMap?.removeLayer(layer);
    }

    moveLayer(uuid: string, oldPos: number, newPos: number): void {
        let layer = this.findLayer(uuid);
        if (layer && layer instanceof ImageLayer) {
            let group: LayerGroup = <LayerGroup>this.findLayer(LAYER_IMAGE);
            let layerll = group.getLayers().remove(layer);
            group.getLayers().insertAt(newPos, layerll as Base);
        }
    }

    findLayer(uuid: string): Base|undefined {
        let layers = this.olMap?.getLayers().getArray() as Base[];
        for (let layer of layers) {
            // let source: ol.source.Source;
            if (layer.get(UUID) === uuid) {
                return layer;
            } else if (layer instanceof LayerGroup) {
                let sublayers = (<LayerGroup>layer).getLayers().getArray();
                for (let sublayer of sublayers) {
                    if (sublayer.get(UUID) === uuid) {
                        return sublayer;
                    }
                }
            }
        }
    }

    updateMap(): void {
        this.olMap?.updateSize();
        this.zoomToExtent(this.startExtent?.getPolygonForExtent(this.olMap?.getView().getProjection()));
    }

    changeCrs(crs: string) { // TODO
        const toProj = GeomUtils.getProjection(crs);
        if (toProj) {
            let extent = MtdExtent.fromArray(
                this.olMap?.getView().calculateExtent(this.olMap?.getSize() as Size) as [number, number, number, number],
                this.getProjection()
            );
            let fromProj = this.getProjection();
            // let center = this.olMap?.getView().getCenter();
            // let layers = (<LayerGroup>this.findLayer(LAYER_IMAGE)).getLayers().getArray();
            this.olMap?.setView(
                this.createView(
                    toProj,
                    this.maxExtent?.getExtent(toProj),
                    (GeomUtils.resolutionsForScales(this.scales, toProj.getUnits() as 'degrees'|'m'|'ft'|'radians'|'us-ft') as number[]).reverse()
                )
            );
            this.changeForILayersI((<LayerGroup>this.findLayer(LAYER_IMAGE)).getLayers(), fromProj, toProj);
            this.changeForVLayers((<LayerGroup>this.findLayer(LAYER_VECTOR)).getLayers(), fromProj, toProj);
            this.zoomToExtent(extent.getPolygonForExtent(toProj));
        }
    }

    private changeForVLayers(layers: Collection<Base>, fromProj: any, toProj: any) {
        for (let layer of layers.getArray()) {
            this.vecSource.reprojectionSource(layer, fromProj, toProj);
        }
    }

    private changeForILayersI(layers: Collection<Base>, fromProj: any, toProj: any) {
        for (let layer of layers.getArray()) {
            this.wmsSource.reprojectionSource(layer, fromProj, toProj);
            let source = (layer as any).getSource() as ImageWMS;
            let ilf = source.getImageLoadFunction();
            source.setImageLoadFunction(ilf);
        }
    }

    setVisible(layer: Base | string, visiblity: boolean): void {
        let _layer = layer instanceof Base ? layer : this.findLayer(<string>layer);
        if (_layer) {
            _layer.setVisible(visiblity);
        }
    }

    setOpacity(layer: Base | string, opacity: number): void {
        let _layer = layer instanceof Base ? layer : this.findLayer(<string>layer);
        if (_layer) {
            _layer.setOpacity(opacity);
        }
    }

    clearFeatures() {
        this.vecSource.clearFeatures(this.hgLayer as VectorLayer);
    }

    showFeatures(geoJson: Object) {
        this.vecSource.showFeatures(this.hgLayer as VectorLayer, geoJson);
    }

    getFirstGeomForSearch(): GeoJSONFeature {
        let features = this.drawer?.getLayer()?.getSource()?.getFeatures()??[];
        if (features.length === 0) {
            return undefined;
        }
        let geojson = new GeoJSON().writeFeatureObject(
            features[0],
            {
                'dataProjection': METADOR_EPSG,
                'featureProjection': this.getProjection()
            }
        );
        geojson['bbox'] = new MtdGeom(features[0].getGeometry(), this.getProjection())
            .getExtent(olProj.get(METADOR_EPSG) as olProj.Projection);
        return geojson;
    }

    drawGeometryForSearch(geoJson: Object, onDrawEnd: (geojson: any) => void) {
        // const mtdmap = this;
        // const olMap = this.olMap;
        this.vecSource.clearFeatures(this.drawer.getLayer());
        this.vecSource.showFeatures(this.drawer.getLayer(), geoJson);
        let multiPoint: MultiPoint = MtdExtent.fromArray(
            this.drawer?.getLayer()?.getSource()?.getExtent() as [number, number, number, number],
            this.olMap?.getView().getProjection()
        ).getGeom() as MultiPoint;
        let maxextent = this.maxExtent?.getPolygonForExtent(this.olMap?.getView().getProjection());
        if (maxextent.intersectsCoordinate(multiPoint.getPoint(0).getCoordinates())
            || maxextent.intersectsCoordinate(multiPoint.getPoint(1).getCoordinates())) {
            this.zoomToExtent(this.drawer?.getLayer()?.getSource()?.getExtent() as [number, number, number, number]);
        } else {
            metador.displayError('Die Geometrie ist außerhalb der räumlichen Erstreckung.');
        }
        let geoFeature = this.getFirstGeomForSearch();
        if (onDrawEnd !== null && geoFeature) {
            onDrawEnd(geoFeature);
        }
    }

    drawShapeForSearch(shapeType: SHAPES, onDrawEnd: (geojson: any) => void) {
        this.setDoubleClickZoom(false);
        const mtdmap = this;
        const olMap = this.olMap as OlMap;

        // const shape: SHAPES = typeof shapeType === 'string' ? SHAPES[<string>shapeType] : shapeType;
        if (this.drawer.getInteraction()) {
            this.olMap?.removeInteraction(this.drawer.getInteraction() as any);
        }
        this.drawer.setInteraction(shapeType, GeomUtils.getStyle(this.styles['search']));
        if (this.drawer.getInteraction()) {
            const drawer = this.drawer;
            this.getDrawer()?.getLayer()?.getSource()?.clear();
            this.olMap?.addInteraction(this.drawer.getInteraction() as any);
            this.drawer.getInteraction()?.on(
                'drawstart',
                function (e: any) {
                    mtdmap.getDrawer()?.getLayer()?.getSource()?.clear();
                }
            );
            this.drawer?.getInteraction()?.on(
                'drawend',
                function (e: any) { // TODO replace any with real class name
                    let geojson = new GeoJSON().writeFeatureObject(
                        e.feature,
                        {
                            'dataProjection': METADOR_EPSG,
                            'featureProjection': mtdmap.getProjection()
                        }
                    );
                    geojson['bbox'] = new MtdGeom(e.feature.getGeometry(), mtdmap.getProjection())
                        .getExtent(GeomUtils.getProjection(METADOR_EPSG as string) as olProj.Projection);
                    onDrawEnd(geojson);
                    olMap.removeInteraction(drawer.getInteraction() as any);
                }
            );
        } else {
            this.getDrawer()?.getLayer()?.getSource()?.clear();
            onDrawEnd(null);
            this.setDoubleClickZoom(true);
        }
    }

    private setDoubleClickZoom(state: boolean) {
        for (let interaction of this.olMap?.getInteractions()?.getArray()??[]) {
            if (interaction instanceof DoubleClickZoom) {
                interaction.setActive(state);
                break;
            }
        }
    }
}


declare var metador: any;
