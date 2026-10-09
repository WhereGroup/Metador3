import {ProjectionLike} from "ol/proj";
import Base from "ol/layer/Base";

export abstract class MtdSource {

    abstract createLayer(layerUuid: string, options: any, proj: ProjectionLike, visible: boolean, opacity: number): Base;

    abstract reprojectionSource(layer: Base, fromProj: ProjectionLike, toProj: ProjectionLike): void;

    abstract cloneLayer(layer: Base, fromProj: ProjectionLike, toProj: ProjectionLike): Base;
}
