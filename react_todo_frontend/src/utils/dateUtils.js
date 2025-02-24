import moment from "moment";

export const formatDate = (date) => {
    return date ? moment(date).format("MMMM Do YYYY, h:mm a") : "None";
};

export const isPastDue = (date) => {
    return date && moment(date).isBefore(moment());
};

export const fromNow = (date) => {
    return date ? moment(date).fromNow() : "N/A";
};